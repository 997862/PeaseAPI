<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Option;

class GroupController
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $groups = Option::get('Groups', 'default');
        $groupList = explode(",", $groups);
        $result = array_map(fn($g) => ['name' => trim($g)], $groupList);

        return Response::success($result);
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $groups = $request->input('groups', []);
        if (empty($groups)) {
            return Response::error('Groups are required');
        }

        $groupStr = implode(",", array_map('trim', $groups));
        Option::updateOption('Groups', $groupStr);
        return Response::success(null, 'Groups updated successfully');
    }
}
