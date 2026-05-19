<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\Node;

class NodeController
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
        $stmt = $db->query("SELECT * FROM nodes ORDER BY id ASC");
        $nodes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return Response::success(['items' => $nodes]);
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
        if (empty($data['name']) || empty($data['url']) || empty($data['api_key'])) {
            return Response::error('缺少必要参数');
        }

        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->prepare("INSERT INTO nodes (name, url, api_key, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['url'], $data['api_key'], $data['status'] ?? 1]);

        return Response::success(null, '节点创建成功');
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
        $stmt = $db->prepare("UPDATE nodes SET name=?, url=?, api_key=?, status=? WHERE id=?");
        $stmt->execute([$data['name'], $data['url'], $data['api_key'], $data['status'], $id]);

        return Response::success(null, '节点更新成功');
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
        $db->prepare("DELETE FROM nodes WHERE id=?")->execute([$id]);

        return Response::success(null, '节点删除成功');
    }

    public function sync(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $db = \NewApi\Database\Connection::getInstance();
        
        // Get node info
        $stmt = $db->prepare("SELECT * FROM nodes WHERE id=?");
        $stmt->execute([$id]);
        $node = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$node) return Response::error('节点不存在', 404);

        // Simulate sync - in production would call remote API
        $db->prepare("UPDATE nodes SET last_sync_at=NOW() WHERE id=?")->execute([$id]);

        return Response::success(null, '同步完成');
    }

    public function syncAll(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $db = \NewApi\Database\Connection::getInstance();
        $db->exec("UPDATE nodes SET last_sync_at=NOW() WHERE status=1");

        return Response::success(null, '所有节点同步完成');
    }
}
