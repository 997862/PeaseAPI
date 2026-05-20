<?php
// Manual .env loading
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \"'");
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

require __DIR__ . '/../vendor/autoload.php';
use NewApi\Database\Connection;

$db = Connection::getInstance();

// Create mail_templates table
$db->exec("
CREATE TABLE IF NOT EXISTS mail_templates (
    id SERIAL PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    content TEXT NOT NULL,
    description TEXT,
    variables JSON DEFAULT '[]',
    is_active BOOLEAN DEFAULT true,
    is_system BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
");

echo "mail_templates table ready" . PHP_EOL;

// Check existing templates
$count = $db->query("SELECT COUNT(*) FROM mail_templates")->fetchColumn();
if ($count > 0) {
    echo "Found {$count} existing templates" . PHP_EOL;
    $stmt = $db->query("SELECT id, slug, name FROM mail_templates ORDER BY id");
    while ($row = $stmt->fetch()) {
        echo "  - [{$row['id']}] {$row['slug']} ({$row['name']})" . PHP_EOL;
    }
    exit(0);
}

$templates = [
    [
        'slug' => 'email_verification',
        'name' => '邮箱验证',
        'subject' => '【{site_name}】请验证您的邮箱地址',
        'description' => '用户注册时发送的邮箱验证码邮件',
        'variables' => '["username", "verification_code"]',
        'is_active' => true,
        'is_system' => true,
    ],
    [
        'slug' => 'password_reset',
        'name' => '密码重置',
        'subject' => '【{site_name}】密码重置验证码',
        'description' => '用户请求重置密码时发送的验证码邮件',
        'variables' => '["username", "verification_code"]',
        'is_active' => true,
        'is_system' => true,
    ],
    [
        'slug' => 'topup_notification',
        'name' => '充值通知',
        'subject' => '【{site_name}】充值成功通知',
        'description' => '用户充值成功后发送的通知邮件',
        'variables' => '["username", "amount", "quota", "payment_method", "created_at"]',
        'is_active' => true,
        'is_system' => true,
    ],
];

// Email verification content
$templates[0]['content'] = '<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:"Microsoft YaHei",sans-serif;background:#f5f5f5;padding:40px 0;margin:0}
.container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:linear-gradient(135deg,#6366F1,#8B5CF6);padding:30px;text-align:center;color:#fff}
.header h1{margin:0;font-size:24px}
.body{padding:40px 30px}
.body p{color:#333;line-height:1.8;margin:0 0 16px}
.code-box{background:#f0f0ff;border:2px dashed #6366F1;border-radius:8px;padding:20px;text-align:center;margin:24px 0}
.code{font-size:36px;font-weight:bold;color:#6366F1;letter-spacing:8px}
.footer{background:#f9fafb;padding:20px 30px;text-align:center;color:#999;font-size:12px;border-top:1px solid #eee}
</style></head><body>
<div class="container">
<div class="header"><h1>🫛 {site_name}</h1></div>
<div class="body">
<p>亲爱的 <strong>{username}</strong>，您好！</p>
<p>感谢您注册 {site_name}。为了保障您的账户安全，请验证您的邮箱地址。</p>
<div class="code-box">
<p style="margin:0 0 8px;color:#666;font-size:14px">您的验证码是：</p>
<div class="code">{verification_code}</div>
</div>
<p style="color:#999;font-size:14px">验证码有效期为 <strong>10 分钟</strong>，请及时使用。</p>
<p style="color:#999;font-size:14px">如果您没有注册 {site_name} 账户，请忽略此邮件。</p>
</div>
<div class="footer"><p>此邮件由系统自动发送，请勿直接回复。</p><p>{site_name} · {site_url}</p></div>
</div></body></html>';

// Password reset content
$templates[1]['content'] = '<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:"Microsoft YaHei",sans-serif;background:#f5f5f5;padding:40px 0;margin:0}
.container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:linear-gradient(135deg,#EF4444,#F59E0B);padding:30px;text-align:center;color:#fff}
.header h1{margin:0;font-size:24px}
.body{padding:40px 30px}
.body p{color:#333;line-height:1.8;margin:0 0 16px}
.code-box{background:#fff7ed;border:2px dashed #F59E0B;border-radius:8px;padding:20px;text-align:center;margin:24px 0}
.code{font-size:36px;font-weight:bold;color:#F59E0B;letter-spacing:8px}
.footer{background:#f9fafb;padding:20px 30px;text-align:center;color:#999;font-size:12px;border-top:1px solid #eee}
.warning{background:#fef3c7;border-left:4px solid #F59E0B;padding:12px 16px;margin:16px 0;border-radius:4px}
</style></head><body>
<div class="container">
<div class="header"><h1>🔐 {site_name} 密码重置</h1></div>
<div class="body">
<p>亲爱的 <strong>{username}</strong>，您好！</p>
<p>我们收到了您重置密码的请求。请使用以下验证码完成密码重置：</p>
<div class="code-box">
<p style="margin:0 0 8px;color:#666;font-size:14px">您的验证码是：</p>
<div class="code">{verification_code}</div>
</div>
<div class="warning">
<p style="margin:0;color:#92400e;font-size:14px">⚠️ 安全提醒：如果您没有请求重置密码，请立即忽略此邮件。验证码有效期为 10 分钟。</p>
</div>
</div>
<div class="footer"><p>此邮件由系统自动发送，请勿直接回复。</p><p>{site_name} · {site_url}</p></div>
</div></body></html>';

// Topup notification content
$templates[2]['content'] = '<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:"Microsoft YaHei",sans-serif;background:#f5f5f5;padding:40px 0;margin:0}
.container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:linear-gradient(135deg,#10B981,#059669);padding:30px;text-align:center;color:#fff}
.header h1{margin:0;font-size:24px}
.body{padding:40px 30px}
.body p{color:#333;line-height:1.8;margin:0 0 16px}
.info-box{background:#ecfdf5;border-radius:8px;padding:20px;margin:24px 0}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #d1fae5}
.info-row:last-child{border-bottom:none}
.info-label{color:#6b7280}
.info-value{font-weight:bold;color:#059669}
.footer{background:#f9fafb;padding:20px 30px;text-align:center;color:#999;font-size:12px;border-top:1px solid #eee}
</style></head><body>
<div class="container">
<div class="header"><h1>💰 {site_name} 充值成功</h1></div>
<div class="body">
<p>亲爱的 <strong>{username}</strong>，您好！</p>
<p>您的账户已成功充值，详情如下：</p>
<div class="info-box">
<div class="info-row"><span class="info-label">充值金额</span><span class="info-value">¥{amount}</span></div>
<div class="info-row"><span class="info-label">到账额度</span><span class="info-value">{quota}</span></div>
<div class="info-row"><span class="info-label">支付方式</span><span class="info-value">{payment_method}</span></div>
<div class="info-row"><span class="info-label">交易时间</span><span class="info-value">{created_at}</span></div>
</div>
<p>登录 {site_name} 查看您的账户余额。</p>
</div>
<div class="footer"><p>此邮件由系统自动发送，请勿直接回复。</p><p>{site_name} · {site_url}</p></div>
</div></body></html>';

foreach ($templates as $t) {
    $stmt = $db->prepare("
        INSERT INTO mail_templates (slug, name, subject, content, description, variables, is_active, is_system, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON CONFLICT (slug) DO NOTHING
    ");
    $stmt->execute([
        $t['slug'], $t['name'], $t['subject'], $t['content'],
        $t['description'], $t['variables'],
        $t['is_active'] ? 't' : 'f',
        $t['is_system'] ? 't' : 'f',
    ]);
    echo "Created: {$t['name']} ({$t['slug']})" . PHP_EOL;
}

echo PHP_EOL . "Mail templates initialized successfully!" . PHP_EOL;
