<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;

class GroupController2
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->query("SELECT g.*, COUNT(ugm.user_id) as member_count FROM user_groups g LEFT JOIN user_group_members ugm ON g.id=ugm.group_id GROUP BY g.id ORDER BY g.id ASC");
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return Response::success(['items' => $groups]);
    }

    public function create(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $data = $request->all();
        if (empty($data['name'])) {
            return Response::error('分组名称不能为空');
        }

        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->prepare("INSERT INTO user_groups (name, description, quota_limit, rate_limit) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['description'] ?? '', $data['quota_limit'] ?? 0, $data['rate_limit'] ?? 0]);

        return Response::success(null, '分组创建成功');
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $data = $request->all();

        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->prepare("UPDATE user_groups SET name=?, description=?, quota_limit=?, rate_limit=? WHERE id=?");
        $stmt->execute([$data['name'], $data['description'] ?? '', $data['quota_limit'] ?? 0, $data['rate_limit'] ?? 0, $id]);

        return Response::success(null, '分组更新成功');
    }

    public function delete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $db = \NewApi\Database\Connection::getInstance();
        
        // Remove members first
        $db->prepare("DELETE FROM user_group_members WHERE group_id=?")->execute([$id]);
        $db->prepare("DELETE FROM user_groups WHERE id=?")->execute([$id]);

        return Response::success(null, '分组删除成功');
    }

    public function addMember(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $groupId = $request->input('group_id');
        $memberId = $request->input('user_id');

        $db = \NewApi\Database\Connection::getInstance();
        
        try {
            $stmt = $db->prepare("INSERT INTO user_group_members (user_id, group_id) VALUES (?, ?)");
            $stmt->execute([$memberId, $groupId]);
            
            // Update user's group_id
            $db->prepare("UPDATE users SET group_id=? WHERE id=?")->execute([$groupId, $memberId]);
        } catch (\Exception $e) {
            return Response::error('添加失败，用户可能已在分组中');
        }

        return Response::success(null, '添加成功');
    }

    public function removeMember(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $groupId = $request->input('group_id');
        $memberId = $request->input('user_id');

        $db = \NewApi\Database\Connection::getInstance();
        $db->prepare("DELETE FROM user_group_members WHERE user_id=? AND group_id=?")->execute([$memberId, $groupId]);
        $db->prepare("UPDATE users SET group_id=0 WHERE id=? AND group_id=?")->execute([$memberId, $groupId]);

        return Response::success(null, '移除成功');
    }
}
