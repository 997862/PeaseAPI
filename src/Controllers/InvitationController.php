<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;

class InvitationController
{
    // 获取当前用户的邀请码
    public function getMyInvite(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $db = \NewApi\Database\Connection::getInstance();
        
        // Get user's invite code
        $stmt = $db->prepare("SELECT invite_code FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $inviteCode = $user['invite_code'] ?? '';
        
        // Generate if empty
        if (empty($inviteCode)) {
            $inviteCode = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $db->prepare("UPDATE users SET invite_code=? WHERE id=?")->execute([$inviteCode, $userId]);
        }

        // Get invite stats
        $stmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(reward_quota), 0) as total_reward FROM invitation_logs WHERE inviter_id=?");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Get recent invitations
        $stmt = $db->prepare("SELECT il.*, u.username as invitee_username FROM invitation_logs il LEFT JOIN users u ON il.invitee_id=u.id WHERE il.inviter_id=? ORDER BY il.created_at DESC LIMIT 20");
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $frontendUrl = \NewApi\Models\Option::get('FrontendURL', '');
        $inviteUrl = $frontendUrl . '/user/register.html?invite=' . $inviteCode;

        return Response::success([
            'invite_code' => $inviteCode,
            'invite_url' => $inviteUrl,
            'total_invited' => (int)$stats['total'],
            'total_reward' => (int)$stats['total_reward'],
            'logs' => $logs
        ]);
    }

    // 使用邀请码注册
    public function redeemInvite(Request $request): Response
    {
        $inviteCode = $request->input('invite_code');
        if (empty($inviteCode)) {
            return Response::error('邀请码不能为空');
        }

        $db = \NewApi\Database\Connection::getInstance();
        
        // Find inviter
        $stmt = $db->prepare("SELECT id FROM users WHERE invite_code=?");
        $stmt->execute([$inviteCode]);
        $inviter = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$inviter) {
            return Response::error('邀请码无效');
        }

        return Response::success(['inviter_id' => $inviter['id']], '邀请码验证成功');
    }

    // 管理员：查看邀请统计
    public function getStats(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $db = \NewApi\Database\Connection::getInstance();
        
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 20);
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare("SELECT COUNT(*) FROM invitations WHERE status=1");
        $stmt->execute();
        $total = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT i.*, u1.username as inviter_name, u2.username as invitee_name 
            FROM invitations i 
            LEFT JOIN users u1 ON i.inviter_id = u1.id 
            LEFT JOIN users u2 ON i.invitee_id = u2.id 
            WHERE i.status=1 
            ORDER BY i.created_at DESC LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success(['items' => $items, 'total' => $total]);
    }

    // 管理员：设置奖励配额
    public function setReward(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $quota = (int)$request->input('reward_quota', 1000000); // Default 1 million
        \NewApi\Models\Option::set('InvitationRewardQuota', (string)$quota);
        
        return Response::success(null, '奖励配额设置成功');
    }
}
