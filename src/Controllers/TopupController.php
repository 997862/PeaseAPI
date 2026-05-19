<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Topup;

class TopupController
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
        $result = Topup::paginate($page, $perPage);
        return Response::success($result);
    }

    public function getUserTopups(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');

        $page = (int) $request->query('p', 1);
        $perPage = (int) $request->query('per_page', 10);
        $result = Topup::getByUserId($userId, $page, $perPage);
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

        $targetUserId = (int) $request->input('user_id', 0);
        $quota = (int) $request->input('quota', 0);
        $amount = (float) $request->input('amount', 0);

        if ($targetUserId <= 0) {
            return Response::error('User ID is required');
        }

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return Response::error('User not found', 404);
        }

        $topup = Topup::create([
            'user_id' => $targetUserId,
            'quota' => $quota,
            'amount' => $amount,
            'status' => 1,
            'created_at' => time(),
            'paid_at' => time(),
        ]);

        $targetUser->quota += $quota;
        $targetUser->save();

        return Response::success($topup->toArray(), 'Topup successful');
    }
}
