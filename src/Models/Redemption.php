<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;

class Redemption extends Model
{
    protected static string $table = 'redemptions';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'key', 'status', 'token', 'created_time', 'redeemed_time', 'count', 'quota'];
    protected static array $casts = ['id' => 'int', 'user_id' => 'int', 'status' => 'int', 'created_time' => 'int', 'redeemed_time' => 'int', 'count' => 'int', 'quota' => 'int'];

    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 2;
    public const STATUS_USED = 3;

    public static function generateKey(): string
    {
        return strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 16));
    }

    public static function getByKey(string $key): ?static
    {
        return static::firstWhere('key', $key);
    }

    public static function paginate(int $page = 1, int $perPage = 10, array $conditions = [], string $orderBy = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM redemptions ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $total = static::count();
        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'last_page' => (int) ceil($total / $perPage)];
    }
}
