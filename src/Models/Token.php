<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;

class Token extends Model
{
    protected static string $table = 'tokens';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [
        'id', 'user_id', 'name', 'key', 'created_time', 'accessed_time',
        'expired_time', 'remain_quota', 'unlimited_quota', 'status',
        'group', 'model_limit', 'used_quota', 'fetch_time', 'heartbeat_time',
        'ip_limit',
    ];
    protected static array $casts = [
        'id' => 'int', 'user_id' => 'int', 'created_time' => 'int',
        'accessed_time' => 'int', 'expired_time' => 'int',
        'remain_quota' => 'int', 'unlimited_quota' => 'bool',
        'status' => 'int', 'used_quota' => 'int',
        'model_limit' => 'json',
    ];

    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 2;

    public static function generateKey(): string
    {
        return 'sk-' . bin2hex(random_bytes(16));
    }

    public static function getByKey(string $key): ?static
    {
        return static::firstWhere('key', $key);
    }

    public static function getByUserId(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM tokens WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $total = static::count(['user_id' => $userId]);
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function isExpired(): bool
    {
        if ($this->expired_time === null || $this->expired_time === 0) {
            return false;
        }
        return time() > $this->expired_time;
    }

    public function hasEnoughQuota(int $quota): bool
    {
        if ($this->unlimited_quota) {
            return true;
        }
        return $this->remain_quota >= $quota;
    }

    public function consumeQuota(int $quota): bool
    {
        if ($this->unlimited_quota) {
            $this->used_quota += $quota;
            $this->save();
            return true;
        }
        if ($this->remain_quota < $quota) {
            return false;
        }
        $this->remain_quota -= $quota;
        $this->used_quota += $quota;
        $this->accessed_time = time();
        $this->save();
        return true;
    }

    public function getModelLimit(): ?array
    {
        if (empty($this->model_limit)) {
            return null;
        }
        return json_decode($this->model_limit, true);
    }

    public function updateAccessedTime(): void
    {
        $db = Connection::getInstance();
        $sql = "UPDATE tokens SET accessed_time = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([time(), $this->id]);
    }

    public function updateHeartbeat(): void
    {
        $db = Connection::getInstance();
        $sql = "UPDATE tokens SET heartbeat_time = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([time(), $this->id]);
    }

    public function getGroup(): string
    {
        return $this->group ?: 'default';
    }
}
