<?php
/**
 * Migration: Add payment fields to topups table and create payment options
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use NewApi\Database\Connection;

$db = Connection::getInstance();

echo "Running payment migration...\n";

// Add missing columns to topups
$columns = [
    'order_no VARCHAR(64) DEFAULT NULL',
    'method VARCHAR(20) DEFAULT NULL',
    'trade_no VARCHAR(64) DEFAULT NULL',
    'updated_at BIGINT DEFAULT 0',
];

foreach ($columns as $col) {
    $colName = explode(' ', $col)[0];
    $sql = "ALTER TABLE topups ADD COLUMN IF NOT EXISTS {$col}";
    try {
        $db->exec($sql);
        echo "✓ Added column: {$colName}\n";
    } catch (Exception $e) {
        echo "- Column {$colName} already exists or error: " . $e->getMessage() . "\n";
    }
}

// Create payment options if not exist
$options = [
    'PaymentEnabled' => 'false',
    'AlipayEnabled' => 'false',
    'WeChatPayEnabled' => 'false',
    'AlipayAppID' => '',
    'AlipayPrivateKey' => '',
    'AlipayPublicKey' => '',
    'AlipayNotifyURL' => '',
    'WeChatPayAppID' => '',
    'WeChatPayMchID' => '',
    'WeChatPayAPIKey' => '',
    'WeChatPayNotifyURL' => '',
    'WeChatPayCertPath' => '',
    'MinTopupAmount' => '1.00',
    'TopupRatio' => '1.0',
];

foreach ($options as $key => $value) {
    $check = $db->prepare("SELECT key FROM options WHERE \"key\" = ?");
    $check->execute([$key]);
    if (!$check->fetch()) {
        $insert = $db->prepare("INSERT INTO options (\"key\", value) VALUES (?, ?)");
        $insert->execute([$key, $value]);
        echo "✓ Created option: {$key}\n";
    } else {
        echo "- Option {$key} already exists\n";
    }
}

echo "\n✅ Payment migration complete!\n";
