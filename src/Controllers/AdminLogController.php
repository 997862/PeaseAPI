<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Database\Connection;

class AdminLogController
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $page = (int) $request->query('p', 1);
        $perPage = (int) $request->query('per_page', 10);
        $offset = ($page - 1) * $perPage;

        $db = Connection::getInstance();
        
        // 使用正确的字段名：admin_id→user_id, admin_username→username
        $stmt = $db->query("SELECT COUNT(*) FROM admin_logs");
        $total = (int) $stmt->fetchColumn();
        
        $stmt = $db->query("
            SELECT id, admin_id as user_id, admin_username as username, action, ip, created_at 
            FROM admin_logs 
            ORDER BY created_at DESC 
            LIMIT $perPage OFFSET $offset
        ");
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success([
            'count' => $total,
            'rows' => $logs,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ]);
    }
}
