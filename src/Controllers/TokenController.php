<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\Token;
use NewApi\Models\User;

class TokenController
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $page = (int) $request->query('p', 1);
        $perPage = (int) $request->query('per_page', 10);

        $result = Token::getByUserId($userId, $page, $perPage);

        // Mask keys
        foreach ($result['items'] as &$item) {
            if (!empty($item['key'])) {
                $item['key'] = \NewApi\Utils\mask_key($item['key']);
            }
        }

        return Response::success($result);
    }

    public function create(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user) {
            return Response::error('User not found', 404);
        }

        $name = $request->input('name');
        if (empty($name)) {
            return Response::error('Token name is required');
        }

        $expireTime = $request->input('expired_time', 0);
        $remainQuota = (int) $request->input('remain_quota', 0);
        $unlimitedQuota = (bool) $request->input('unlimited_quota', false);
        $group = $request->input('group', $user->group ?: 'default');
        $modelLimit = $request->input('model_limit'); // JSON array or null

        $token = Token::create([
            'user_id' => $userId,
            'name' => $name,
            'key' => Token::generateKey(),
            'created_time' => time(),
            'accessed_time' => 0,
            'expired_time' => (int) $expireTime,
            'remain_quota' => $unlimitedQuota ? 0 : $remainQuota,
            'unlimited_quota' => $unlimitedQuota,
            'status' => TOKEN_STATUS_ENABLED,
            'group' => $group,
            'model_limit' => is_array($modelLimit) ? json_encode($modelLimit, JSON_UNESCAPED_UNICODE) : ($modelLimit ?: ''),
            'used_quota' => 0,
        ]);

        return Response::success($token->toArray(), 'Token created successfully');
    }

    public function get(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $id = (int) $request->param('id');

        $token = Token::find($id);
        if (!$token || $token->user_id !== $userId) {
            return Response::error('Token not found', 404);
        }

        return Response::success($token->toArray());
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $id = (int) $request->param('id');

        $token = Token::find($id);
        if (!$token || $token->user_id !== $userId) {
            return Response::error('Token not found', 404);
        }

        $fields = ['name', 'expired_time', 'remain_quota', 'unlimited_quota', 'status', 'group', 'model_limit'];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value !== null) {
                if ($field === 'model_limit') {
                    $token->$field = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                } else {
                    $token->$field = $value;
                }
            }
        }

        $token->save();
        return Response::success($token->toArray(), 'Token updated successfully');
    }

    public function delete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $id = (int) $request->param('id');

        $token = Token::find($id);
        if (!$token || $token->user_id !== $userId) {
            return Response::error('Token not found', 404);
        }

        $token->delete();
        return Response::success(null, 'Token deleted successfully');
    }

    public function batchDelete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return Response::error('No tokens selected');
        }

        // Only delete tokens belonging to this user
        foreach ($ids as $id) {
            $token = Token::find((int) $id);
            if ($token && $token->user_id === $userId) {
                $token->delete();
            }
        }

        return Response::success(null, 'Tokens deleted successfully');
    }

    public function batchUpdateStatus(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $ids = $request->input('ids', []);
        $status = (int) $request->input('status');

        if (empty($ids)) {
            return Response::error('No tokens selected');
        }

        foreach ($ids as $id) {
            $token = Token::find((int) $id);
            if ($token && $token->user_id === $userId) {
                $token->status = $status;
                $token->save();
            }
        }

        return Response::success(null, 'Token status updated successfully');
    }
}
