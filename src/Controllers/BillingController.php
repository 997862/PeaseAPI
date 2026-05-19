<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Log;
use NewApi\Database\Connection;

class BillingController
{
    public function report(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $user = User::find($userId);
        if (!$user) return Response::error('User not found', 404);

        $isAdmin = $user->role >= ROLE_ADMIN_USER;
        $days = (int) $request->query('days', 30);
        $startTime = time() - ($days * 86400);

        $db = Connection::getInstance();

        if ($isAdmin && $request->query('all')) {
            // 全局报表
            $sql = "SELECT DATE(TO_TIMESTAMP(created_at)) as date, COUNT(*) as requests, SUM(quota) as total_quota, SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens FROM logs WHERE created_at >= ? GROUP BY date ORDER BY date DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$startTime]);
            $report = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $totalSql = "SELECT COUNT(*) as total_requests, SUM(quota) as total_quota FROM logs WHERE created_at >= ?";
            $stmt = $db->prepare($totalSql);
            $stmt->execute([$startTime]);
            $total = $stmt->fetch();

            return Response::success(['report' => $report, 'total' => $total, 'days' => $days]);
        }

        // 用户报表
        $sql = "SELECT DATE(TO_TIMESTAMP(created_at)) as date, COUNT(*) as requests, SUM(quota) as total_quota, SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens FROM logs WHERE user_id = ? AND created_at >= ? GROUP BY date ORDER BY date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $startTime]);
        $report = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalSql = "SELECT COUNT(*) as total_requests, SUM(quota) as total_quota FROM logs WHERE user_id = ? AND created_at >= ?";
        $stmt = $db->prepare($totalSql);
        $stmt->execute([$userId, $startTime]);
        $total = $stmt->fetch();

        return Response::success(['report' => $report, 'total' => $total, 'days' => $days]);
    }

    public function userReport(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) return Response::error('Admin access required', 403);

        $targetUserId = (int) $request->query('user_id', 0);
        $days = (int) $request->query('days', 30);
        $startTime = time() - ($days * 86400);

        $db = Connection::getInstance();
        $sql = "SELECT DATE(TO_TIMESTAMP(created_at)) as date, COUNT(*) as requests, SUM(quota) as total_quota FROM logs WHERE user_id = ? AND created_at >= ? GROUP BY date ORDER BY date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$targetUserId, $startTime]);
        $report = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success(['report' => $report, 'days' => $days]);
    }
}
