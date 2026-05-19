<?php
require __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

try {
    $db = \NewApi\Database\Connection::getInstance();
    
    $tables = [
        'tokens' => "ALTER TABLE tokens ADD COLUMN IF NOT EXISTS ip_limit TEXT DEFAULT ''",
        'user_groups' => "CREATE TABLE IF NOT EXISTS user_groups (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            quota_limit BIGINT DEFAULT 0,
            rate_limit INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'roles' => "CREATE TABLE IF NOT EXISTS roles (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            permissions JSONB DEFAULT '{}',
            min_quota BIGINT DEFAULT 0,
            max_quota BIGINT DEFAULT 0,
            description TEXT,
            sort_order INTEGER DEFAULT 0
        )",
        'invitations' => "CREATE TABLE IF NOT EXISTS invitations (
            id SERIAL PRIMARY KEY,
            inviter_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            invitee_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            invite_code VARCHAR(20) NOT NULL UNIQUE,
            reward_quota BIGINT DEFAULT 0,
            status INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(inviter_id, invitee_id)
        )",
        'invitation_logs' => "CREATE TABLE IF NOT EXISTS invitation_logs (
            id SERIAL PRIMARY KEY,
            inviter_id INTEGER NOT NULL,
            invitee_id INTEGER NOT NULL,
            reward_quota BIGINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'nodes' => "CREATE TABLE IF NOT EXISTS nodes (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            url VARCHAR(500) NOT NULL,
            api_key VARCHAR(200) NOT NULL,
            status INTEGER DEFAULT 1,
            last_sync_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'realtime_metrics' => "CREATE TABLE IF NOT EXISTS realtime_metrics (
            id SERIAL PRIMARY KEY,
            metric_type VARCHAR(50) NOT NULL,
            value NUMERIC(10,4) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
    ];
    
    foreach ($tables as $name => $sql) {
        // Check if table exists
        $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)");
        $stmt->execute([$name]);
        $exists = $stmt->fetchColumn();
        
        if ($exists && strpos($sql, 'CREATE TABLE') !== false) {
            echo "⊘ Table '$name' already exists\n";
            continue;
        }
        
        try {
            $db->exec($sql);
            echo "✓ Created/modified: $name\n";
        } catch (Exception $e) {
            echo "✗ Failed $name: " . $e->getMessage() . "\n";
        }
    }
    
    // Add columns to users if not exists
    $columns = [
        ['users', 'invite_code', "ALTER TABLE users ADD COLUMN IF NOT EXISTS invite_code VARCHAR(20) DEFAULT ''"],
        ['users', 'invited_by', "ALTER TABLE users ADD COLUMN IF NOT EXISTS invited_by INTEGER REFERENCES users(id)"],
        ['users', 'group_id', "ALTER TABLE users ADD COLUMN IF NOT EXISTS group_id INTEGER DEFAULT 0"],
        ['users', 'role', null], // Already exists
    ];
    
    foreach ($columns as [$table, $col, $sql]) {
        if (!$sql) continue;
        $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = ? AND column_name = ?)");
        $stmt->execute([$table, $col]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            echo "⊘ Column '$table.$col' already exists\n";
            continue;
        }
        
        try {
            $db->exec($sql);
            echo "✓ Added column: $table.$col\n";
        } catch (Exception $e) {
            echo "✗ Failed $table.$col: " . $e->getMessage() . "\n";
        }
    }
    
    // Check tokens.ip_limit
    $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'tokens' AND column_name = 'ip_limit')");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $db->exec("ALTER TABLE tokens ADD COLUMN ip_limit TEXT DEFAULT ''");
        echo "✓ Added column: tokens.ip_limit\n";
    } else {
        echo "⊘ Column tokens.ip_limit already exists\n";
    }
    
    // Insert default roles
    $roles = [
        ['user', '普通用户', '{"create_token": true, "view_log": true, "redeem": true}', '基础用户权限，可使用 API', 1],
        ['vip', 'VIP 用户', '{"create_token": true, "view_log": true, "redeem": true, "priority": true}', 'VIP 用户，更高优先级', 2],
        ['admin', '管理员', '{"create_token": true, "view_log": true, "redeem": true, "priority": true, "manage_users": true, "manage_channels": true}', '管理后台权限', 3],
        ['root', '超级管理员', '{}', '最高权限，所有功能', 4],
    ];
    
    foreach ($roles as [$name, $display, $perms, $desc, $sort]) {
        $stmt = $db->prepare("SELECT EXISTS (SELECT FROM roles WHERE name = ?)");
        $stmt->execute([$name]);
        if (!$stmt->fetchColumn()) {
            $db->prepare("INSERT INTO roles (name, display_name, permissions, description, sort_order) VALUES (?, ?, ?::jsonb, ?, ?)")
                ->execute([$name, $display, $perms, $desc, $sort]);
            echo "✓ Inserted role: $name\n";
        } else {
            echo "⊘ Role '$name' already exists\n";
        }
    }
    
    echo "\n✓ Migration completed successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
