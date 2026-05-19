<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Log;

class UserController
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $page = (int) $request->query('p', 1);
        $perPage = (int) $request->query('per_page', 10);
        $keyword = $request->query('keyword', '');

        if ($keyword) {
            $result = User::searchUsers($keyword, $page, $perPage);
        } else {
            // 直接使用 PDO 查询，绕过 Model 序列化问题
            $db = \NewApi\Database\Connection::getInstance();
            $offset = ($page - 1) * $perPage;

            $countStmt = $db->prepare("SELECT COUNT(*) FROM users");
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $stmt = $db->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
            $stmt->execute([$perPage, $offset]);
            $rawItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $result = [
                'items' => $rawItems,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ];
        }

        return Response::success($result);
    }

    public function get(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $targetUser = User::find($id);
        if (!$targetUser) {
            return Response::error('User not found', 404);
        }

        return Response::success($targetUser->toArray());
    }

    public function create(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $username = $request->input('username');
        $password = $request->input('password');
        $email = $request->input('email');
        $quota = (int) $request->input('quota', 0);

        if (empty($username)) {
            return Response::error('Username is required');
        }

        if (User::usernameExists($username)) {
            return Response::error('Username already exists');
        }

        $newUser = User::create([
            'username' => $username,
            'password' => $password ? \NewApi\Utils\hash_password($password) : \NewApi\Utils\hash_password(bin2hex(random_bytes(8))),
            'display_name' => $username,
            'role' => (int) $request->input('role', ROLE_COMMON_USER),
            'status' => (int) $request->input('status', USER_STATUS_ENABLED),
            'email' => $email ?: '',
            'quota' => $quota,
            'group' => $request->input('group', 'default'),
        ]);
        $newUser->generateAffCode();

        return Response::success($newUser->toArray(), 'User created successfully');
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $targetUser = User::find($id);
        if (!$targetUser) {
            return Response::error('User not found', 404);
        }

        $fields = ['username', 'display_name', 'role', 'status', 'email', 'group', 'remark'];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value !== null) {
                if ($field === 'role' || $field === 'status') {
                    $targetUser->$field = (int) $value;
                } else {
                    $targetUser->$field = $value;
                }
            }
        }

        // Set quota
        $quota = $request->input('quota');
        if ($quota !== null) {
            $targetUser->quota = (int) $quota;
        }

        $targetUser->save();
        return Response::success($targetUser->toArray(), 'User updated successfully');
    }

    public function delete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $targetUser = User::find($id);
        if (!$targetUser) {
            return Response::error('User not found', 404);
        }

        if ($targetUser->role >= $user->role) {
            return Response::error('Cannot delete user with equal or higher role');
        }

        $targetUser->delete();
        return Response::success(null, 'User deleted successfully');
    }

    public function manage(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $targetUserId = (int) $request->input('user_id');
        $quota = (int) $request->input('quota', 0);
        $action = $request->input('action', 'add');

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return Response::error('User not found', 404);
        }

        if ($action === 'add') {
            $targetUser->quota += $quota;
        } elseif ($action === 'subtract') {
            $targetUser->quota = max(0, $targetUser->quota - $quota);
        }

        $targetUser->save();
        return Response::success(null, 'Quota updated');
    }

    // 批量操作用户
    public function batchAction(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $userIds = $request->input('user_ids', []);
        $action = $request->input('action', '');
        
        if (empty($userIds) || !is_array($userIds)) {
            return Response::error('请选择要操作的用户');
        }

        $db = \NewApi\Database\Connection::getInstance();
        $successCount = 0;
        $failCount = 0;

        switch ($action) {
            case 'enable':
                $stmt = $db->prepare("UPDATE users SET status=1 WHERE id=? AND role < ?");
                foreach ($userIds as $id) {
                    $stmt->execute([(int)$id, $user->role]);
                    $successCount++;
                }
                break;
            case 'disable':
                $stmt = $db->prepare("UPDATE users SET status=2 WHERE id=? AND role < ?");
                foreach ($userIds as $id) {
                    $stmt->execute([(int)$id, $user->role]);
                    $successCount++;
                }
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM users WHERE id=? AND role < ?");
                foreach ($userIds as $id) {
                    $stmt->execute([(int)$id, $user->role]);
                    $successCount++;
                }
                break;
            case 'add_quota':
                $quota = (int)$request->input('quota', 0);
                if ($quota <= 0) return Response::error('配额必须大于0');
                $stmt = $db->prepare("UPDATE users SET quota=quota+? WHERE id=? AND role < ?");
                foreach ($userIds as $id) {
                    $stmt->execute([$quota, (int)$id, $user->role]);
                    $successCount++;
                }
                break;
            case 'reset_quota':
                $stmt = $db->prepare("UPDATE users SET quota=0, used_quota=0 WHERE id=? AND role < ?");
                foreach ($userIds as $id) {
                    $stmt->execute([(int)$id, $user->role]);
                    $successCount++;
                }
                break;
            case 'change_role':
                $newRole = (int)$request->input('new_role', 1);
                if ($newRole >= $user->role) return Response::error('无法设置高于自己的角色');
                $stmt = $db->prepare("UPDATE users SET role=? WHERE id=? AND role < ?");
                foreach ($userIds as $id) {
                    $stmt->execute([$newRole, (int)$id, $user->role]);
                    $successCount++;
                }
                break;
            default:
                return Response::error('未知操作类型');
        }

        return Response::success(['success' => $successCount, 'fail' => $failCount], "批量操作完成");
    }
}
