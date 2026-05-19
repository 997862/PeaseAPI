<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;

class MetricsController
{
    public function getRealtime(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $db = \NewApi\Database\Connection::getInstance();
        
        // QPS (last 5 minutes)
        $stmt = $db->query("
            SELECT COUNT(*) / 300.0 as qps FROM logs 
            WHERE created_at > NOW() - INTERVAL '5 minutes'
        ");
        $qps = $stmt->fetchColumn() ?: 0;

        // Average latency (last 5 minutes)
        $stmt = $db->query("
            SELECT AVG(response_time) as avg_latency FROM logs 
            WHERE created_at > NOW() - INTERVAL '5 minutes' AND response_time > 0
        ");
        $avgLatency = $stmt->fetchColumn() ?: 0;

        // Token usage (last 1 hour)
        $stmt = $db->query("
            SELECT COALESCE(SUM(total_tokens), 0) as token_usage FROM logs 
            WHERE created_at > NOW() - INTERVAL '1 hour'
        ");
        $tokenUsage = $stmt->fetchColumn() ?: 0;

        // Error rate (last 5 minutes)
        $stmt = $db->query("
            SELECT COUNT(*) FROM logs WHERE created_at > NOW() - INTERVAL '5 minutes'
        ");
        $totalReqs = $stmt->fetchColumn() ?: 0;
        
        $stmt = $db->query("
            SELECT COUNT(*) FROM logs 
            WHERE created_at > NOW() - INTERVAL '5 minutes' AND status_code >= 400
        ");
        $errorReqs = $stmt->fetchColumn() ?: 0;
        
        $errorRate = $totalReqs > 0 ? ($errorReqs / $totalReqs * 100) : 0;

        // Active channels
        $stmt = $db->query("SELECT COUNT(*) FROM channels WHERE status=1");
        $activeChannels = $stmt->fetchColumn() ?: 0;

        // Active tokens (used in last 24h)
        $stmt = $db->query("
            SELECT COUNT(DISTINCT token_id) FROM logs 
            WHERE created_at > NOW() - INTERVAL '24 hours'
        ");
        $activeTokens = $stmt->fetchColumn() ?: 0;

        // Quota usage (last 24h)
        $stmt = $db->query("
            SELECT COALESCE(SUM(quota), 0) as quota_usage FROM logs 
            WHERE created_at > NOW() - INTERVAL '24 hours'
        ");
        $quotaUsage = $stmt->fetchColumn() ?: 0;

        return Response::success([
            'qps' => round((float)$qps, 2),
            'avg_latency_ms' => round((float)$avgLatency, 2),
            'token_usage' => (int)$tokenUsage,
            'error_rate' => round((float)$errorRate, 2),
            'active_channels' => (int)$activeChannels,
            'active_tokens' => (int)$activeTokens,
            'quota_usage' => (int)$quotaUsage,
            'total_requests_5m' => (int)$totalReqs,
            'error_requests_5m' => (int)$errorReqs,
        ]);
    }

    public function getTrend(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $hours = (int)$request->input('hours', 24);
        
        $db = \NewApi\Database\Connection::getInstance();
        
        $stmt = $db->prepare("
            SELECT 
                DATE_TRUNC('hour', created_at) as hour,
                COUNT(*) as requests,
                COALESCE(SUM(total_tokens), 0) as tokens,
                COALESCE(SUM(quota), 0) as quota,
                AVG(response_time) as avg_latency
            FROM logs 
            WHERE created_at > NOW() - INTERVAL '? hours'
            GROUP BY DATE_TRUNC('hour', created_at)
            ORDER BY hour ASC
        ");
        $stmt->execute([$hours]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success(['items' => $data]);
    }
}
