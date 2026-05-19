<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;

class SystemToken extends Model
{
    protected static string $table = 'system_tokens';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'token', 'name', 'role', 'user_id', 'status', 'created_at', 'expires_at'];
    protected static array $casts = ['id' => 'int', 'role' => 'int', 'user_id' => 'int', 'status' => 'int', 'created_at' => 'int', 'expires_at' => 'int'];

    public static function validate(string $token): ?static
    {
        $st = static::firstWhere('token', $token);
        if (!$st || $st->status !== 1) return null;
        if ($st->expires_at > 0 && $st->expires_at < time()) return null;
        return $st;
    }

    public static function generate(): string
    {
        return 'st-' . bin2hex(random_bytes(24));
    }
}
