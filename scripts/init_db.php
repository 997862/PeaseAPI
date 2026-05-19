#!/usr/bin/env php
<?php
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

echo "=== New API PHP - PostgreSQL Database Initialization ===\n\n";
$dbType = $_ENV['DB_TYPE'] ?? getenv('DB_TYPE') ?: 'mysql';
echo "Database Type: $dbType\n";

try {
    $db = Connection::getInstance();
    echo "✓ Database connected successfully\n\n";

    if ($dbType !== 'postgres') {
        echo "⚠ This script is optimized for PostgreSQL. Current DB_TYPE: $dbType\n\n";
    }

    $schemas = getPostgresSchemas();

    // Create tables
    foreach ($schemas as $name => $sql) {
        try {
            $db->exec($sql);
            echo "✓ Table '$name' created\n";
        } catch (\PDOException $e) {
            // Ignore if already exists
            if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), '42P07') !== false) {
                echo "○ Table '$name' already exists (skipped)\n";
            } else {
                echo "✗ Table '$name' failed: " . $e->getMessage() . "\n";
            }
        }
    }

    // Create indexes
    $indexes = getPostgresIndexes();
    foreach ($indexes as $sql) {
        try {
            $db->exec($sql);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '42P07') === false) {
                echo "  Index skip: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "✓ Indexes created\n";

    echo "\n=== Database initialization complete! ===\n";

} catch (\Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

function getPostgresSchemas(): array
{
    return [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(20) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(20) DEFAULT '',
            role SMALLINT NOT NULL DEFAULT 1,
            status SMALLINT NOT NULL DEFAULT 1,
            email VARCHAR(50) DEFAULT '',
            github_id VARCHAR(64) DEFAULT '',
            discord_id VARCHAR(64) DEFAULT '',
            oidc_id VARCHAR(64) DEFAULT '',
            wechat_id VARCHAR(64) DEFAULT '',
            telegram_id VARCHAR(64) DEFAULT '',
            linux_do_id VARCHAR(64) DEFAULT '',
            quota INTEGER NOT NULL DEFAULT 0,
            used_quota INTEGER NOT NULL DEFAULT 0,
            request_count INTEGER NOT NULL DEFAULT 0,
            \"group\" VARCHAR(64) DEFAULT 'default',
            aff_code VARCHAR(32) UNIQUE DEFAULT '',
            aff_count INTEGER DEFAULT 0,
            aff_quota INTEGER DEFAULT 0,
            aff_history_quota INTEGER DEFAULT 0,
            inviter_id INTEGER DEFAULT NULL,
            access_token CHAR(32) UNIQUE DEFAULT NULL,
            setting TEXT DEFAULT NULL,
            remark VARCHAR(255) DEFAULT '',
            stripe_customer VARCHAR(64) DEFAULT '',
            created_at BIGINT DEFAULT 0,
            last_login_at BIGINT DEFAULT 0
        )",

        'channels' => "CREATE TABLE IF NOT EXISTS channels (
            id SERIAL PRIMARY KEY,
            type SMALLINT NOT NULL DEFAULT 1,
            key TEXT NOT NULL,
            openai_organization VARCHAR(255) DEFAULT '',
            test_model VARCHAR(128) DEFAULT '',
            status SMALLINT NOT NULL DEFAULT 1,
            name VARCHAR(255) NOT NULL DEFAULT '',
            weight INTEGER DEFAULT 0,
            created_time BIGINT NOT NULL DEFAULT 0,
            test_time BIGINT DEFAULT 0,
            response_time INTEGER DEFAULT 0,
            base_url VARCHAR(1024) DEFAULT '',
            other TEXT DEFAULT NULL,
            balance DOUBLE PRECISION DEFAULT 0,
            balance_updated_time BIGINT DEFAULT 0,
            models TEXT DEFAULT '',
            \"group\" VARCHAR(64) DEFAULT 'default',
            used_quota BIGINT DEFAULT 0,
            model_mapping TEXT DEFAULT NULL,
            status_code_mapping VARCHAR(1024) DEFAULT '',
            priority BIGINT DEFAULT 0,
            auto_ban SMALLINT DEFAULT 1,
            other_info TEXT DEFAULT NULL,
            tag VARCHAR(255) DEFAULT NULL,
            setting TEXT DEFAULT NULL,
            param_override TEXT DEFAULT NULL,
            header_override TEXT DEFAULT NULL,
            remark VARCHAR(255) DEFAULT '',
            channel_info JSONB DEFAULT NULL,
            settings TEXT DEFAULT NULL
        )",

        'tokens' => "CREATE TABLE IF NOT EXISTS tokens (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL DEFAULT '',
            key VARCHAR(64) NOT NULL UNIQUE,
            created_time BIGINT NOT NULL DEFAULT 0,
            accessed_time BIGINT DEFAULT 0,
            expired_time BIGINT DEFAULT 0,
            remain_quota BIGINT DEFAULT 0,
            unlimited_quota BOOLEAN DEFAULT FALSE,
            status SMALLINT NOT NULL DEFAULT 1,
            \"group\" VARCHAR(64) DEFAULT 'default',
            model_limit TEXT DEFAULT NULL,
            used_quota BIGINT DEFAULT 0,
            fetch_time BIGINT DEFAULT 0,
            heartbeat_time BIGINT DEFAULT 0
        )",

        'logs' => "CREATE TABLE IF NOT EXISTS logs (
            id BIGSERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            channel_id INTEGER DEFAULT NULL,
            model_name VARCHAR(255) NOT NULL DEFAULT '',
            quota BIGINT NOT NULL DEFAULT 0,
            content TEXT DEFAULT NULL,
            request_id VARCHAR(64) DEFAULT '',
            trace TEXT DEFAULT NULL,
            created_at BIGINT NOT NULL DEFAULT 0,
            type SMALLINT NOT NULL DEFAULT 1,
            is_stream BOOLEAN DEFAULT FALSE,
            original_model_name VARCHAR(255) DEFAULT '',
            \"group\" VARCHAR(64) DEFAULT '',
            prompt_tokens INTEGER DEFAULT 0,
            completion_tokens INTEGER DEFAULT 0,
            total_tokens INTEGER DEFAULT 0
        )",

        'abilities' => "CREATE TABLE IF NOT EXISTS abilities (
            \"group\" VARCHAR(64) NOT NULL,
            model VARCHAR(255) NOT NULL,
            channel_id INTEGER NOT NULL,
            enabled BOOLEAN DEFAULT TRUE,
            priority BIGINT DEFAULT 0,
            weight INTEGER DEFAULT 0,
            tag VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (\"group\", model, channel_id)
        )",

        'options' => "CREATE TABLE IF NOT EXISTS options (
            \"key\" VARCHAR(128) PRIMARY KEY,
            value TEXT DEFAULT ''
        )",

        'redemptions' => "CREATE TABLE IF NOT EXISTS redemptions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER DEFAULT NULL,
            key VARCHAR(32) NOT NULL UNIQUE,
            status SMALLINT NOT NULL DEFAULT 1,
            token VARCHAR(255) NOT NULL UNIQUE,
            created_time BIGINT NOT NULL DEFAULT 0,
            redeemed_time BIGINT DEFAULT 0,
            count INTEGER NOT NULL DEFAULT 1,
            quota BIGINT NOT NULL DEFAULT 0
        )",

        'subscriptions' => "CREATE TABLE IF NOT EXISTS subscriptions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            product_id VARCHAR(64) DEFAULT '',
            status SMALLINT NOT NULL DEFAULT 1,
            start_at BIGINT DEFAULT 0,
            end_at BIGINT DEFAULT 0,
            cancel_at BIGINT DEFAULT 0,
            trial_at BIGINT DEFAULT 0,
            quota INTEGER DEFAULT 0,
            auto_renew BOOLEAN DEFAULT TRUE
        )",

        'checkins' => "CREATE TABLE IF NOT EXISTS checkins (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            quota INTEGER NOT NULL DEFAULT 0,
            created_at BIGINT NOT NULL DEFAULT 0
        )",

        'topups' => "CREATE TABLE IF NOT EXISTS topups (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            amount DOUBLE PRECISION NOT NULL DEFAULT 0,
            quota INTEGER NOT NULL DEFAULT 0,
            status SMALLINT NOT NULL DEFAULT 0,
            payment_id VARCHAR(128) DEFAULT '',
            payment_method VARCHAR(32) DEFAULT '',
            created_at BIGINT NOT NULL DEFAULT 0,
            paid_at BIGINT DEFAULT 0
        )",

        'oauth_bindings' => "CREATE TABLE IF NOT EXISTS oauth_bindings (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            provider VARCHAR(32) NOT NULL,
            provider_id VARCHAR(128) NOT NULL,
            created_at BIGINT NOT NULL DEFAULT 0,
            CONSTRAINT unique_provider UNIQUE (provider, provider_id)
        )",

        'pricing' => "CREATE TABLE IF NOT EXISTS pricing (
            id SERIAL PRIMARY KEY,
            model_name VARCHAR(128) NOT NULL DEFAULT '',
            unit_price DOUBLE PRECISION NOT NULL DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'USD',
            type VARCHAR(32) DEFAULT 'per_token',
            created_at BIGINT DEFAULT 0,
            updated_at BIGINT DEFAULT 0
        )",

        'prefill_groups' => "CREATE TABLE IF NOT EXISTS prefill_groups (
            id SERIAL PRIMARY KEY,
            name VARCHAR(128) NOT NULL DEFAULT '',
            models JSONB DEFAULT NULL,
            created_at BIGINT DEFAULT 0
        )",

        'passkeys' => "CREATE TABLE IF NOT EXISTS passkeys (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            name VARCHAR(128) DEFAULT '',
            credential_id VARCHAR(255) NOT NULL,
            public_key TEXT DEFAULT NULL,
            counter INTEGER DEFAULT 0,
            created_at BIGINT DEFAULT 0
        )",

        'twofa_secrets' => "CREATE TABLE IF NOT EXISTS twofa_secrets (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL UNIQUE,
            secret VARCHAR(128) NOT NULL,
            enabled BOOLEAN DEFAULT FALSE,
            created_at BIGINT DEFAULT 0
        )",

        'missing_models' => "CREATE TABLE IF NOT EXISTS missing_models (
            id SERIAL PRIMARY KEY,
            model_name VARCHAR(128) NOT NULL DEFAULT '',
            channel_id INTEGER NOT NULL,
            created_at BIGINT DEFAULT 0
        )",

        'perf_metrics' => "CREATE TABLE IF NOT EXISTS perf_metrics (
            id BIGSERIAL PRIMARY KEY,
            metric_name VARCHAR(128) NOT NULL,
            metric_value TEXT NOT NULL,
            created_at BIGINT NOT NULL DEFAULT 0
        )",

        'vendor_meta' => "CREATE TABLE IF NOT EXISTS vendor_meta (
            id SERIAL PRIMARY KEY,
            vendor_name VARCHAR(128) NOT NULL DEFAULT '',
            vendor_type VARCHAR(32) NOT NULL DEFAULT '',
            base_url VARCHAR(512) DEFAULT '',
            config TEXT DEFAULT NULL,
            created_at BIGINT DEFAULT 0
        )",
    ];
}

function getPostgresIndexes(): array
{
    return [
        "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_display_name ON users(display_name)",
        "CREATE INDEX IF NOT EXISTS idx_users_inviter_id ON users(inviter_id)",
        "CREATE INDEX IF NOT EXISTS idx_users_aff_code ON users(aff_code)",

        "CREATE INDEX IF NOT EXISTS idx_channels_name ON channels(name)",
        "CREATE INDEX IF NOT EXISTS idx_channels_status ON channels(status)",
        "CREATE INDEX IF NOT EXISTS idx_channels_type ON channels(type)",
        "CREATE INDEX IF NOT EXISTS idx_channels_tag ON channels(tag)",

        "CREATE INDEX IF NOT EXISTS idx_tokens_user_id ON tokens(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_tokens_key ON tokens(key)",
        "CREATE INDEX IF NOT EXISTS idx_tokens_status ON tokens(status)",

        "CREATE INDEX IF NOT EXISTS idx_logs_user_id ON logs(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_logs_channel_id ON logs(channel_id)",
        "CREATE INDEX IF NOT EXISTS idx_logs_created_at ON logs(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_logs_model_name ON logs(model_name)",

        "CREATE INDEX IF NOT EXISTS idx_abilities_model ON abilities(model)",
        "CREATE INDEX IF NOT EXISTS idx_abilities_channel_id ON abilities(channel_id)",
        "CREATE INDEX IF NOT EXISTS idx_abilities_enabled ON abilities(enabled)",

        "CREATE INDEX IF NOT EXISTS idx_redemptions_key ON redemptions(key)",
        "CREATE INDEX IF NOT EXISTS idx_redemptions_status ON redemptions(status)",
        "CREATE INDEX IF NOT EXISTS idx_redemptions_user_id ON redemptions(user_id)",

        "CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status)",

        "CREATE INDEX IF NOT EXISTS idx_checkins_user_id ON checkins(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_checkins_created_at ON checkins(created_at)",

        "CREATE INDEX IF NOT EXISTS idx_topups_user_id ON topups(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_topups_status ON topups(status)",
        "CREATE INDEX IF NOT EXISTS idx_topups_payment_id ON topups(payment_id)",

        "CREATE INDEX IF NOT EXISTS idx_oauth_user_id ON oauth_bindings(user_id)",

        "CREATE INDEX IF NOT EXISTS idx_pricing_model_name ON pricing(model_name)",

        "CREATE INDEX IF NOT EXISTS idx_passkeys_user_id ON passkeys(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_passkeys_credential_id ON passkeys(credential_id)",

        "CREATE INDEX IF NOT EXISTS idx_twofa_user_id ON twofa_secrets(user_id)",

        "CREATE INDEX IF NOT EXISTS idx_missing_models_model_name ON missing_models(model_name)",
        "CREATE INDEX IF NOT EXISTS idx_missing_models_channel_id ON missing_models(channel_id)",

        "CREATE INDEX IF NOT EXISTS idx_perf_metrics_metric_name ON perf_metrics(metric_name)",
        "CREATE INDEX IF NOT EXISTS idx_perf_metrics_created_at ON perf_metrics(created_at)",

        "CREATE INDEX IF NOT EXISTS idx_vendor_meta_vendor_name ON vendor_meta(vendor_name)",
    ];
}
