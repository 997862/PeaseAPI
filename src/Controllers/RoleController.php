<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;

class RoleController
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
        $stmt = $db->query("SELECT * FROM roles ORDER BY sort_order ASC");
        $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Decode JSONB permissions
        foreach ($roles as &$role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?: [];
        }

        return Response::success(['items' => $roles]);
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
        if (empty($data['name']) || empty($data['display_name'])) {
            return Response::error('缺少名称或显示名称');
        }

        $db = \NewApi\Database\Connection::getInstance();
        $permissions = json_encode($data['permissions'] ?? []);
        
        $stmt = $db->prepare("INSERT INTO roles (name, display_name, permissions, description, min_quota, max_quota, sort_order) VALUES (?, ?, ?::jsonb, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['display_name'],
            $permissions,
            $data['description'] ?? '',
            $data['min_quota'] ?? 0,
            $data['max_quota'] ?? 0,
            $data['sort_order'] ?? 0
        ]);

        return Response::success(null, '角色创建成功');
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
        $permissions = isset($data['permissions']) ? json_encode($data['permissions']) : null;

        $fields = [];
        $values = [];
        if (isset($data['name'])) { $fields[] = 'name=?'; $values[] = $data['name']; }
        if (isset($data['display_name'])) { $fields[] = 'display_name=?'; $values[] = $data['display_name']; }
        if ($permissions !== null) { $fields[] = 'permissions=?::jsonb'; $values[] = $permissions; }
        if (isset($data['description'])) { $fields[] = 'description=?'; $values[] = $data['description']; }
        if (isset($data['min_quota'])) { $fields[] = 'min_quota=?'; $values[] = $data['min_quota']; }
        if (isset($data['max_quota'])) { $fields[] = 'max_quota=?'; $values[] = $data['max_quota']; }
        if (isset($data['sort_order'])) { $fields[] = 'sort_order=?'; $values[] = $data['sort_order']; }

        if (empty($fields)) return Response::error('没有可更新的字段');

        $values[] = $id;
        $sql = "UPDATE roles SET " . implode(', ', $fields) . " WHERE id=?";
        $db->prepare($sql)->execute($values);

        return Response::success(null, '角色更新成功');
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
        
        // Check if role is in use
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = (SELECT id FROM roles WHERE id=?)");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return Response::error('该角色下有用户，无法删除');
        }

        $db->prepare("DELETE FROM roles WHERE id=?")->execute([$id]);
        return Response::success(null, '角色删除成功');
    }
}
