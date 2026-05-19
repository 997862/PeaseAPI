<?php
namespace NewApi\Models;
use NewApi\Database\Model;
use NewApi\Database\Connection;

class Topup extends Model {
    protected static string $table = 'topups';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'amount', 'quota', 'status', 'payment_id', 'payment_method', 'created_at', 'paid_at'];
    protected static array $casts = ['id'=>'int','user_id'=>'int','amount'=>'float','quota'=>'int','status'=>'int','created_at'=>'int','paid_at'=>'int'];
    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_FAILED = 2;

    public static function paginate(int $page = 1, int $perPage = 10, array $conditions = [], string $orderBy = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM topups ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $total = static::count();
        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'last_page' => (int) ceil($total / $perPage)];
    }

    public static function getByUserId(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM topups WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $total = static::count(['user_id' => $userId]);
        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'last_page' => (int) ceil($total / $perPage)];
    }
}
