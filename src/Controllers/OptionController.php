<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Option;

class OptionController
{
    public function list(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $options = Option::getAll();
        return Response::success($options);
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $key = $request->input('key');
        $value = $request->input('value');

        if (empty($key)) {
            return Response::error('Key is required');
        }

        Option::updateOption($key, $value);
        return Response::success(null, 'Option updated successfully');
    }

    public function batchUpdate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $options = $request->input('options', []);
        if (empty($options)) {
            return Response::error('Options are required');
        }

        foreach ($options as $key => $value) {
            Option::updateOption($key, $value);
        }

        return Response::success(null, 'Options updated successfully');
    }

    public function get(Request $request): Response
    {
        $key = $request->param('key');
        if (empty($key)) {
            return Response::error('Key is required');
        }

        $value = Option::get($key);
        return Response::success(['key' => $key, 'value' => $value]);
    }
}
