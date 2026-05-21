#!/usr/bin/env php
<?php
/**
 * PeaseAPI (豌豆API) v3.0 — 数据库初始化脚本
 * 
 * 使用方法：
 *   php scripts/init_db.php
 * 
 * 依赖：.env 文件中配置数据库连接信息
 */
require_once __DIR__ . '/../vendor/autoload.php';

$envDir = __DIR__ . '/..';
if (!file_exists($envDir . '/.env')) {
    $envDir = '/www/wwwroot/www.peaseapi.com';
}
if (file_exists($envDir . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($envDir);
    $dotenv->load();
}

use NewApi\Database\Connection;

echo "=== PeaseAPI v3.0 数据库初始化 ===\n\n";
$dbType = $_ENV['DB_TYPE'] ?? getenv('DB_TYPE') ?: 'postgres';
echo "数据库类型: $dbType\n";

try {
    $db = Connection::getInstance();
    echo "✓ 数据库连接成功\n\n";

    if ($dbType !== 'postgres') {
        echo "⚠ 此脚本针对 PostgreSQL 优化，当前使用: $dbType\n\n";
    }

    $schemas = getPostgresSchemas();
    $created = 0;
    $skipped = 0;

    foreach ($schemas as $name => $sql) {
        try {
            $db->exec($sql);
            echo "✓ 表 '$name' 创建成功\n";
            $created++;
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), '42P07') !== false) {
                echo "○ 表 '$name' 已存在（跳过）\n";
                $skipped++;
            } else {
                echo "✗ 表 '$name' 创建失败: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n创建: $created 表, 跳过: $skipped 表\n";

    // 创建索引
    $indexes = getPostgresIndexes();
    $idxCreated = 0;
    $idxSkipped = 0;
    foreach ($indexes as $sql) {
        try {
            $db->exec($sql);
            $idxCreated++;
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '42P07') === false) {
                // 忽略已存在的索引
            }
            $idxSkipped++;
        }
    }
    echo "✓ 索引: $idxCreated 创建, $idxSkipped 跳过\n";

    echo "\n=== 数据库初始化完成！ ===\n";

} catch (\Exception $e) {
    echo "✗ 初始化失败: " . $e->getMessage() . "\n";
    exit(1);
}

function getPostgresSchemas(): array
{
    return [

    ];
}

function getPostgresIndexes(): array
{
    return [
    ];
}
