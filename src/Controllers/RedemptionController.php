<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Redemption;

class RedemptionController
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
        $result = Redemption::paginate($page, $perPage);
        return Response::success($result);
    }

    public function create(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $count = (int) $request->input('count', 1);
        $quota = (int) $request->input('quota', 0);

        if ($quota <= 0) {
            return Response::error('Quota must be greater than 0');
        }

        $keys = [];
        for ($i = 0; $i < $count; $i++) {
            $key = Redemption::generateKey();
            $token = 'tk-' . bin2hex(random_bytes(8));

            $redemption = Redemption::create([
                'key' => $key,
                'token' => $token,
                'quota' => $quota,
                'count' => 1,
                'created_time' => time(),
                'status' => 1,
            ]);
            $keys[] = $key;
        }

        return Response::success(['keys' => $keys], 'Redemption codes created successfully');
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
        $redemption = Redemption::find($id);
        if (!$redemption) {
            return Response::error('Redemption code not found', 404);
        }

        $redemption->delete();
        return Response::success(null, 'Redemption code deleted successfully');
    }

    public function search(Request $request): Response
    {
        $key = $request->input('key');
        if (empty($key)) {
            return Response::error('Key is required');
        }

        $redemption = Redemption::firstWhere('key', $key);
        if (!$redemption) {
            return Response::error('Redemption code not found', 404);
        }

        return Response::success($redemption->toArray());
    }

    public function redeem(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $key = $request->input('key');

        if (empty($key)) {
            return Response::error('Key is required');
        }

        $redemption = Redemption::firstWhere('key', $key);
        if (!$redemption) {
            return Response::error('Invalid redemption code', 400);
        }

        if ($redemption->status != 1) {
            return Response::error('This redemption code has been used', 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return Response::error('User not found', 404);
        }

        $redemption->status = 2;
        $redemption->user_id = $userId;
        $redemption->redeemed_time = time();
        $redemption->save();

        $user->quota += $redemption->quota;
        $user->save();

        return Response::success([
            'quota' => $redemption->quota,
            'new_quota' => $user->quota,
        ], 'Redemption successful');
    }
}
