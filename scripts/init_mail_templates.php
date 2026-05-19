<?php
require __DIR__ . '/../vendor/autoload.php';

// Load .env manually (since we're running from CLI script)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use NewApi\Models\Option;

// Create mail_templates table if not exists
try {
    $db = \NewApi\Database\Connection::getInstance();
    
    // Check if table exists
    $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'mail_templates')");
    $exists = $stmt->fetchColumn();
    echo "mail_templates exists: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if (!$exists) {
        echo "Creating mail_templates table...\n";
        
        $db->exec("
            CREATE TABLE mail_templates (
                id SERIAL PRIMARY KEY,
                slug VARCHAR(100) NOT NULL UNIQUE,
                name VARCHAR(200) NOT NULL,
                subject VARCHAR(300) NOT NULL,
                content TEXT NOT NULL,
                description TEXT,
                variables TEXT, -- JSON array of available variables
                is_active BOOLEAN DEFAULT TRUE,
                is_system BOOLEAN DEFAULT FALSE, -- System templates cannot be deleted
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        echo "Table created successfully\n";
        
        // Insert default templates
        $templates = [
            [
                'email_verification',
                '邮箱验证',
                '请验证您的邮箱地址 - {site_name}',
                '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;">
<tr><td style="padding:40px 0;text-align:center;background:#6366F1;border-radius:12px 12px 0 0;">
<h1 style="color:#fff;margin:0;font-size:24px;">🫛 {site_name}</h1>
</td></tr>
<tr><td style="background:#fff;padding:40px 30px;">
<h2 style="color:#333;margin:0 0 20px;font-size:20px;">验证您的邮箱</h2>
<p style="color:#666;line-height:1.6;margin:0 0 20px;">你好，{username}！</p>
<p style="color:#666;line-height:1.6;margin:0 0 30px;">请点击下方按钮验证您的邮箱地址，该链接将在 <strong>24 小时</strong>内有效。</p>
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="text-align:center;padding:20px 0;">
<a href="{verify_url}" style="background:#6366F1;color:#fff;padding:14px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:500;display:inline-block;">验证邮箱</a>
</td></tr>
</table>
<p style="color:#999;font-size:12px;line-height:1.6;margin:20px 0 0;">如果按钮无法点击，请复制以下链接到浏览器打开：</p>
<p style="color:#6366F1;font-size:12px;word-break:break-all;margin:5px 0 0;">{verify_url}</p>
</td></tr>
<tr><td style="background:#f9f9f9;padding:20px 30px;text-align:center;border-radius:0 0 12px 12px;">
<p style="color:#999;font-size:12px;margin:0;">此邮件由系统自动发送，请勿直接回复</p>
<p style="color:#999;font-size:12px;margin:5px 0 0;">© {site_name} · AI 资产管理系统</p>
</td></tr>
</table>
</body>
</html>',
                '用户注册时发送的邮箱验证邮件',
                '["site_name","username","verify_url"]',
                true,
                true
            ],
            [
                'password_reset',
                '找回密码',
                '重置您的账户密码 - {site_name}',
                '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;">
<tr><td style="padding:40px 0;text-align:center;background:#F59E0B;border-radius:12px 12px 0 0;">
<h1 style="color:#fff;margin:0;font-size:24px;">🔐 {site_name}</h1>
</td></tr>
<tr><td style="background:#fff;padding:40px 30px;">
<h2 style="color:#333;margin:0 0 20px;font-size:20px;">重置密码</h2>
<p style="color:#666;line-height:1.6;margin:0 0 20px;">你好，{username}！</p>
<p style="color:#666;line-height:1.6;margin:0 0 30px;">我们收到了您的密码重置请求。请点击下方按钮设置新密码，该链接将在 <strong>1 小时</strong>内有效。</p>
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="text-align:center;padding:20px 0;">
<a href="{reset_url}" style="background:#F59E0B;color:#fff;padding:14px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:500;display:inline-block;">重置密码</a>
</td></tr>
</table>
<p style="color:#999;font-size:12px;line-height:1.6;margin:20px 0 0;">如果您没有请求重置密码，请忽略此邮件。</p>
<p style="color:#999;font-size:12px;line-height:1.6;margin:5px 0 0;">如果按钮无法点击，请复制以下链接到浏览器：</p>
<p style="color:#F59E0B;font-size:12px;word-break:break-all;margin:5px 0 0;">{reset_url}</p>
</td></tr>
<tr><td style="background:#f9f9f9;padding:20px 30px;text-align:center;border-radius:0 0 12px 12px;">
<p style="color:#999;font-size:12px;margin:0;">此邮件由系统自动发送，请勿直接回复</p>
<p style="color:#999;font-size:12px;margin:5px 0 0;">© {site_name} · AI 资产管理系统</p>
</td></tr>
</table>
</body>
</html>',
                '用户请求找回密码时发送',
                '["site_name","username","reset_url"]',
                true,
                true
            ],
            [
                'welcome',
                '欢迎注册',
                '欢迎加入 {site_name}！',
                '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;">
<tr><td style="padding:40px 0;text-align:center;background:#10B981;border-radius:12px 12px 0 0;">
<h1 style="color:#fff;margin:0;font-size:24px;">🎉 {site_name}</h1>
</td></tr>
<tr><td style="background:#fff;padding:40px 30px;">
<h2 style="color:#333;margin:0 0 20px;font-size:20px;">欢迎加入！</h2>
<p style="color:#666;line-height:1.6;margin:0 0 20px;">你好，{username}！</p>
<p style="color:#666;line-height:1.6;margin:0 0 30px;">恭喜您成功注册了 {site_name} 账号。现在您可以使用 AI 网关服务了。</p>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:20px;margin:20px 0;">
<p style="color:#166534;margin:0 0 10px;font-weight:500;">🚀 快速开始：</p>
<ul style="color:#15803d;margin:0;padding-left:20px;">
<li>前往用户中心创建您的第一个 API Token</li>
<li>使用 Token 调用 OpenAI 兼容接口</li>
<li>查看文档了解详细用法</li>
</ul>
</div>
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="text-align:center;padding:10px 0;">
<a href="{dashboard_url}" style="background:#10B981;color:#fff;padding:14px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:500;display:inline-block;">进入用户中心</a>
</td></tr>
</table>
</td></tr>
<tr><td style="background:#f9f9f9;padding:20px 30px;text-align:center;border-radius:0 0 12px 12px;">
<p style="color:#999;font-size:12px;margin:0;">此邮件由系统自动发送，请勿直接回复</p>
<p style="color:#999;font-size:12px;margin:5px 0 0;">© {site_name} · AI 资产管理系统</p>
</td></tr>
</table>
</body>
</html>',
                '用户注册成功后发送的欢迎邮件',
                '["site_name","username","dashboard_url"]',
                true,
                true
            ],
            [
                'topup_notification',
                '充值成功通知',
                '充值成功 - {site_name}',
                '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;">
<tr><td style="padding:40px 0;text-align:center;background:#3B82F6;border-radius:12px 12px 0 0;">
<h1 style="color:#fff;margin:0;font-size:24px;">💰 {site_name}</h1>
</td></tr>
<tr><td style="background:#fff;padding:40px 30px;">
<h2 style="color:#333;margin:0 0 20px;font-size:20px;">充值成功</h2>
<p style="color:#666;line-height:1.6;margin:0 0 20px;">你好，{username}！</p>
<p style="color:#666;line-height:1.6;margin:0 0 20px;">您的账户已成功充值，详情如下：</p>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:20px 0;">
<tr><td style="padding:12px;border:1px solid #e5e7eb;background:#f9fafb;color:#666;width:40%;">充值金额</td><td style="padding:12px;border:1px solid #e5e7eb;font-weight:600;">¥{amount}</td></tr>
<tr><td style="padding:12px;border:1px solid #e5e7eb;background:#f9fafb;color:#666;">获得配额</td><td style="padding:12px;border:1px solid #e5e7eb;font-weight:600;">{quota}</td></tr>
<tr><td style="padding:12px;border:1px solid #e5e7eb;background:#f9fafb;color:#666;">当前余额</td><td style="padding:12px;border:1px solid #e5e7eb;font-weight:600;">{balance}</td></tr>
<tr><td style="padding:12px;border:1px solid #e5e7eb;background:#f9fafb;color:#666;">充值时间</td><td style="padding:12px;border:1px solid #e5e7eb;">{time}</td></tr>
</table>
</td></tr>
<tr><td style="background:#f9f9f9;padding:20px 30px;text-align:center;border-radius:0 0 12px 12px;">
<p style="color:#999;font-size:12px;margin:0;">此邮件由系统自动发送，请勿直接回复</p>
<p style="color:#999;font-size:12px;margin:5px 0 0;">© {site_name} · AI 资产管理系统</p>
</td></tr>
</table>
</body>
</html>',
                '用户充值成功后发送的通知邮件',
                '["site_name","username","amount","quota","balance","time"]',
                true,
                true
            ],
        ];

        foreach ($templates as $t) {
            $stmt = $db->prepare("
                INSERT INTO mail_templates (slug, name, subject, content, description, variables, is_active, is_system)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($t);
            echo "Inserted template: {$t[1]}\n";
        }
        
        echo "\nDefault templates inserted successfully\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
