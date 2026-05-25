<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Log;

class LogController
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user) {
            return Response::error('User not found', 404);
        }
        
        // 管理员可以查看所有日志，普通用户只能查看自己的日志
        $isAdmin = $user->role >= ROLE_ADMIN_USER;

        $page = (int) $request->query('p', 1);
        $perPage = (int) $request->query('per_page', 10);
        $filters = [];

        $keyword = $request->query('keyword', '');
        if ($keyword) {
            $filters['model_name'] = $keyword;
        }

        $type = $request->query('type');
        if ($type !== null) {
            $filters['type'] = (int) $type;
        }

        $channelId = $request->query('channel_id');
        if ($channelId !== null) {
            $filters['channel_id'] = (int) $channelId;
        }

        // Admin can view all logs, or filter by user_id
        $targetUserId = $request->query('user_id');
        if ($targetUserId) {
            $result = Log::getUserLogs((int) $targetUserId, $page, $perPage, $filters);
        } else {
            // Get all logs with optional filters
            $result = Log::getAllLogs($page, $perPage, $filters);
        }

        return Response::success($result);
    }

    public function get(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $log = Log::find($id);
        if (!$log) {
            return Response::error('Log not found', 404);
        }

        return Response::success($log->toArray());
    }

    public function delete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $log = Log::find($id);
        if (!$log) {
            return Response::error('Log not found', 404);
        }

        $log->delete();
        return Response::success(null, 'Log deleted successfully');
    }

    public function clear(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        // Clear logs older than specified time
        $time = (int) $request->input('time', 0);
        if ($time <= 0) {
            return Response::error('Invalid time parameter');
        }

        Log::clearBeforeTime($time);
        return Response::success(null, 'Logs cleared successfully');
    }

    public function stats(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $startTime = (int) $request->query('start_time', 0);
        $endTime = (int) $request->query('end_time', 0);

        $stats = Log::getGlobalStats($startTime, $endTime);
        return Response::success($stats);
    }
}
