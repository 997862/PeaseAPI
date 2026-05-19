<?php

namespace NewApi\Services;

use NewApi\Models\Option;

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private bool $useSsl;

    public function __construct()
    {
        $this->host = Option::get('SmtpHost', '');
        $this->port = (int)Option::get('SmtpPort', '465');
        $this->username = Option::get('SmtpUsername', '');
        $this->password = Option::get('SmtpPassword', '');
        $this->fromEmail = Option::get('SmtpFromEmail', '');
        $this->fromName = Option::get('SmtpFromName', 'PeaseAPI');
        $this->useSsl = Option::getBool('SmtpUseSsl', true);
    }

    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body HTML 正文
     * @param array $extra 额外配置（用于测试覆盖）
     * @return array ['success' => bool, 'message' => string]
     */
    public function send(string $to, string $subject, string $body, array $extra = []): array
    {
        // 检查 SMTP 是否启用
        $enabled = Option::getBool('SmtpEnabled', false);
        if (!$enabled && empty($extra)) {
            return ['success' => false, 'message' => '邮件服务未启用'];
        }

        // 允许测试时覆盖配置
        $host = $extra['host'] ?? $this->host;
        $port = $extra['port'] ?? $this->port;
        $username = $extra['username'] ?? $this->username;
        $password = $extra['password'] ?? $this->password;
        $fromEmail = $extra['from_email'] ?? $this->fromEmail;
        $fromName = $extra['from_name'] ?? $this->fromName;
        $useSsl = isset($extra['use_ssl']) ? (bool)$extra['use_ssl'] : $this->useSsl;

        if (empty($host) || empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'SMTP 配置不完整'];
        }

        if (empty($fromEmail)) {
            $fromEmail = $username;
        }

        // 构建原始邮件
        $boundary = md5(uniqid(time()));
        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode(strip_tags($body)) . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= $body . "\r\n";
        $message .= "--{$boundary}--";

        try {
            // 使用 fsockopen 连接 SMTP 服务器
            $fp = $this->connect($host, $port, $useSsl);
            if (!$fp) {
                return ['success' => false, 'message' => '无法连接 SMTP 服务器'];
            }

            $this->sendSmtpCommand($fp, "EHLO PeaseAPI\r\n", 250);
            
            if ($this->readResponse($fp, 2) === '250') {
                // 服务器支持 EHLO，可能支持 AUTH
            }

            // 尝试 AUTH LOGIN
            $this->sendSmtpCommand($fp, "AUTH LOGIN\r\n", 334);
            $this->sendSmtpCommand($fp, base64_encode($username) . "\r\n", 334);
            $this->sendSmtpCommand($fp, base64_encode($password) . "\r\n", 235);

            // 发送邮件
            $this->sendSmtpCommand($fp, "MAIL FROM: <{$fromEmail}>\r\n", 250);
            $this->sendSmtpCommand($fp, "RCPT TO: <{$to}>\r\n", 250);
            $this->sendSmtpCommand($fp, "DATA\r\n", 354);
            
            fwrite($fp, $headers . "\r\n" . $message . "\r\n.\r\n");
            $response = $this->readResponse($fp);
            
            if (strpos($response, '250') === 0) {
                $this->sendSmtpCommand($fp, "QUIT\r\n", 221);
                fclose($fp);
                return ['success' => true, 'message' => '邮件发送成功'];
            } else {
                fclose($fp);
                return ['success' => false, 'message' => '邮件发送失败：' . trim($response)];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '发送异常：' . $e->getMessage()];
        }
    }

    /**
     * 连接 SMTP 服务器
     */
    private function connect(string $host, int $port, bool $useSsl)
    {
        $timeout = 30;
        $addr = ($useSsl ? 'ssl://' : '') . $host;
        
        $fp = fsockopen($addr, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            // 尝试非 SSL 连接
            if ($useSsl) {
                $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
            }
            if (!$fp) {
                error_log("SMTP Connection failed: {$errno} - {$errstr}");
                return false;
            }
        }

        stream_set_timeout($fp, $timeout);
        // 读取欢迎消息
        $this->readResponse($fp, 220);
        return $fp;
    }

    /**
     * 发送 SMTP 命令并检查响应
     */
    private function sendSmtpCommand($fp, string $command, int $expectedCode = null): string
    {
        fwrite($fp, $command);
        return $this->readResponse($fp, $expectedCode);
    }

    /**
     * 读取 SMTP 响应
     */
    private function readResponse($fp, ?int $expectedCode = null): string
    {
        $response = '';
        while ($line = fgets($fp, 1024)) {
            $response .= $line;
            // 检查是否是完整响应（空格表示最后一行）
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        
        if ($expectedCode !== null && strpos($response, (string)$expectedCode) !== 0) {
            error_log("SMTP unexpected response: expected {$expectedCode}, got: " . trim($response));
        }
        
        return trim($response);
    }

    /**
     * 测试 SMTP 连接
     */
    public function testConnection(array $extra = []): array
    {
        $host = $extra['host'] ?? $this->host;
        $port = $extra['port'] ?? $this->port;
        $username = $extra['username'] ?? $this->username;
        $password = $extra['password'] ?? $this->password;
        $useSsl = isset($extra['use_ssl']) ? (bool)$extra['use_ssl'] : $this->useSsl;

        if (empty($host) || empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'SMTP 配置不完整'];
        }

        try {
            $fp = $this->connect($host, $port, $useSsl);
            if (!$fp) {
                return ['success' => false, 'message' => '无法连接 SMTP 服务器'];
            }

            $this->sendSmtpCommand($fp, "EHLO PeaseAPI\r\n", 250);
            $this->sendSmtpCommand($fp, "AUTH LOGIN\r\n", 334);
            $this->sendSmtpCommand($fp, base64_encode($username) . "\r\n", 334);
            $this->sendSmtpCommand($fp, base64_encode($password) . "\r\n", 235);
            $this->sendSmtpCommand($fp, "QUIT\r\n", 221);
            fclose($fp);

            return ['success' => true, 'message' => 'SMTP 连接测试成功'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '连接失败：' . $e->getMessage()];
        }
    }
}
