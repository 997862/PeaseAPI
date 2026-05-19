<?php

namespace NewApi\Utils;

function json_success(mixed $data = null, string $message = 'success'): string
{
    return json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
}

function json_error(string $message, int $code = 400, mixed $data = null): string
{
    return json_encode([
        'success' => false,
        'message' => $message,
        'code' => $code,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
}

function json_openai_error(array $error): string
{
    return json_encode([
        'error' => $error,
    ], JSON_UNESCAPED_UNICODE);
}

function get_request_id(): string
{
    return sprintf('%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(6))
    );
}

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function generate_token(): string
{
    return 'sk-' . bin2hex(random_bytes(24));
}

function generate_aff_code(): string
{
    return substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
}

function mask_key(string $key): string
{
    if (strlen($key) <= 10) {
        return $key;
    }
    return substr($key, 0, 5) . '...' . substr($key, -4);
}

function sanitize_input(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function get_client_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function bytes_to_mb(int $bytes): float
{
    return $bytes / 1024 / 1024;
}

function mb_to_bytes(float $mb): int
{
    return (int) ($mb * 1024 * 1024);
}

function quota_to_currency(float $quota): float
{
    return $quota / QUOTA_PER_UNIT;
}

function currency_to_quota(float $amount): int
{
    return (int) ($amount * QUOTA_PER_UNIT);
}

function log_info(string $message): void
{
    error_log('[INFO] ' . date('Y-m-d H:i:s') . ' ' . $message);
}

function log_error(string $message): void
{
    error_log('[ERROR] ' . date('Y-m-d H:i:s') . ' ' . $message);
}

function log_debug(string $message): void
{
    if (getenv('APP_DEBUG') === 'true') {
        error_log('[DEBUG] ' . date('Y-m-d H:i:s') . ' ' . $message);
    }
}

function time_ms(): int
{
    return (int) (microtime(true) * 1000);
}

function parse_json_field(?string $value): mixed
{
    if ($value === null || $value === '') {
        return null;
    }
    return json_decode($value, true);
}

function to_json_field(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }
    return json_encode($value, JSON_UNESCAPED_UNICODE);
}
