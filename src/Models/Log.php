<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;

class Log extends Model
{
    protected static string $table = 'logs';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [
        'id', 'user_id', 'channel_id', 'model_name', 'quota',
        'content', 'request_id', 'trace', 'created_at', 'type',
        'is_stream', 'original_model_name', 'group', 'prompt_tokens',
        'completion_tokens', 'total_tokens',
    ];
    protected static array $casts = [
        'id' => 'int', 'user_id' => 'int', 'channel_id' => 'int',
        'quota' => 'int', 'created_at' => 'int', 'type' => 'int',
        'is_stream' => 'bool', 'prompt_tokens' => 'int',
        'completion_tokens' => 'int', 'total_tokens' => 'int',
    ];

    public const TYPE_TEXT = 1;
    public const TYPE_IMAGE = 2;
    public const TYPE_AUDIO = 3;
    public const TYPE_EMBEDDING = 4;

    public static function getUserLogs(int $userId, int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM logs WHERE user_id = ?";
        $bindings = [$userId];

        if (!empty($filters['start_time'])) {
            $sql .= " AND created_at >= ?";
            $bindings[] = $filters['start_time'];
        }
        if (!empty($filters['end_time'])) {
            $sql .= " AND created_at <= ?";
            $bindings[] = $filters['end_time'];
        }
        if (!empty($filters['model_name'])) {
            $sql .= " AND model_name = ?";
            $bindings[] = $filters['model_name'];
        }
        if (!empty($filters['channel_id'])) {
            $sql .= " AND channel_id = ?";
            $bindings[] = $filters['channel_id'];
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $bindings = array_merge($bindings, [$perPage, $offset]);
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $countSql = "SELECT COUNT(*) FROM logs WHERE user_id = ?";
        $countBindings = [$userId];
        if (!empty($filters['start_time'])) { $countSql .= " AND created_at >= ?"; $countBindings[] = $filters['start_time']; }
        if (!empty($filters['end_time'])) { $countSql .= " AND created_at <= ?"; $countBindings[] = $filters['end_time']; }
        if (!empty($filters['model_name'])) { $countSql .= " AND model_name = ?"; $countBindings[] = $filters['model_name']; }
        if (!empty($filters['channel_id'])) { $countSql .= " AND channel_id = ?"; $countBindings[] = $filters['channel_id']; }
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($countBindings);
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'last_page' => (int) ceil($total / $perPage)];
    }

    public static function getStats(int $userId, int $startTime = 0, int $endTime = 0): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT COUNT(*) as total_requests, SUM(quota) as total_quota FROM logs WHERE user_id = ?";
        $bindings = [$userId];
        if ($startTime > 0) { $sql .= " AND created_at >= ?"; $bindings[] = $startTime; }
        if ($endTime > 0) { $sql .= " AND created_at <= ?"; $bindings[] = $endTime; }
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetch();
    }

    public static function getDailyStats(int $userId, int $days = 30): array
    {
        $db = Connection::getInstance();
        $startTime = time() - ($days * 86400);
        $sql = "SELECT DATE(FROM_UNIXTIME(created_at)) as date, COUNT(*) as count, SUM(quota) as quota FROM logs WHERE user_id = ? AND created_at >= ? GROUP BY date ORDER BY date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $startTime]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Admin: Get all logs with filters
    public static function getAllLogs(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM logs WHERE 1=1";
        $bindings = [];

        if (!empty($filters['model_name'])) {
            $sql .= " AND model_name = ?";
            $bindings[] = $filters['model_name'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $bindings[] = $filters['type'];
        }
        if (!empty($filters['channel_id'])) {
            $sql .= " AND channel_id = ?";
            $bindings[] = $filters['channel_id'];
        }
        if (!empty($filters['start_time'])) {
            $sql .= " AND created_at >= ?";
            $bindings[] = $filters['start_time'];
        }
        if (!empty($filters['end_time'])) {
            $sql .= " AND created_at <= ?";
            $bindings[] = $filters['end_time'];
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $bindings = array_merge($bindings, [$perPage, $offset]);
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Count
        $countSql = "SELECT COUNT(*) FROM logs WHERE 1=1";
        $countBindings = [];
        if (!empty($filters['model_name'])) { $countSql .= " AND model_name = ?"; $countBindings[] = $filters['model_name']; }
        if (!empty($filters['type'])) { $countSql .= " AND type = ?"; $countBindings[] = $filters['type']; }
        if (!empty($filters['channel_id'])) { $countSql .= " AND channel_id = ?"; $countBindings[] = $filters['channel_id']; }
        if (!empty($filters['start_time'])) { $countSql .= " AND created_at >= ?"; $countBindings[] = $filters['start_time']; }
        if (!empty($filters['end_time'])) { $countSql .= " AND created_at <= ?"; $countBindings[] = $filters['end_time']; }
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($countBindings);
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'last_page' => (int) ceil($total / $perPage)];
    }

    public static function clearBeforeTime(int $time): int
    {
        $db = Connection::getInstance();
        $sql = "DELETE FROM logs WHERE created_at < ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$time]);
        return $stmt->rowCount();
    }

    public static function getGlobalStats(int $startTime = 0, int $endTime = 0): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT COUNT(*) as total_requests, SUM(quota) as total_quota, COUNT(DISTINCT user_id) as active_users FROM logs WHERE 1=1";
        $bindings = [];
        if ($startTime > 0) { $sql .= " AND created_at >= ?"; $bindings[] = $startTime; }
        if ($endTime > 0) { $sql .= " AND created_at <= ?"; $bindings[] = $endTime; }
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetch();
    }
}
