<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Database\Connection;

class LoginLogController
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
        
        // Get total count
        $stmt = $db->query("SELECT COUNT(*) FROM login_logs");
        $total = (int) $stmt->fetchColumn();
        
        // Get logs
        $stmt = $db->query("
            SELECT id, user_id, username, login_ip as ip, user_agent, login_type, login_time, status 
            FROM login_logs 
            ORDER BY login_time DESC 
            LIMIT $perPage OFFSET $offset
        ");
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success([
            'count' => $total,
            'rows' => $logs,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ]);
    }

    public function listSelf(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $page = (int) $request->query('p', 1);
        $perPage = (int) $request->query('per_page', 20);
        $offset = ($page - 1) * $perPage;

        $db = Connection::getInstance();
        
        // Get total count for this user
        $stmt = $db->prepare("SELECT COUNT(*) FROM login_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = (int) $stmt->fetchColumn();
        
        // Get logs for this user
        $stmt = $db->prepare("
            SELECT id, user_id, username, login_ip as ip, user_agent, login_type, login_time, status 
            FROM login_logs 
            WHERE user_id = ?
            ORDER BY login_time DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $perPage, $offset]);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success([
            'count' => $total,
            'rows' => $logs,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ]);
    }
}
