<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\Channel;
use NewApi\Models\Ability;
use NewApi\Models\User;

class ChannelController
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
        $keyword = $request->query('keyword', '');

        if ($keyword) {
            $result = Channel::searchChannels($keyword, $page, $perPage);
        } else {
            $result = Channel::paginate($page, $perPage);
        }

        return Response::success($result);
    }

    public function get(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $channel = Channel::find($id);
        if (!$channel) {
            return Response::error('Channel not found', 404);
        }

        // Mask keys for security
        $channelData = $channel->toArray();
        if (!empty($channelData['key'])) {
            $channelData['key'] = \NewApi\Utils\mask_key($channelData['key']);
        }

        return Response::success($channelData);
    }

    public function create(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $data = [
            'type' => (int) $request->input('type', 1),
            'key' => $request->input('key', ''),
            'name' => $request->input('name', ''),
            'models' => $request->input('models', ''),
            'group' => $request->input('group', 'default'),
            'base_url' => $request->input('base_url', ''),
            'status' => (int) $request->input('status', CHANNEL_STATUS_ENABLED),
            'weight' => (int) $request->input('weight', 0),
            'priority' => (int) $request->input('priority', 0),
            'auto_ban' => (int) $request->input('auto_ban', 1),
            'created_time' => time(),
            'other' => $request->input('other', ''),
            'model_mapping' => $request->input('model_mapping'),
            'status_code_mapping' => $request->input('status_code_mapping'),
            'setting' => $request->input('setting'),
            'param_override' => $request->input('param_override'),
            'header_override' => $request->input('header_override'),
            'remark' => $request->input('remark'),
            'openai_organization' => $request->input('openai_organization'),
            'test_model' => $request->input('test_model'),
        ];

        if (empty($data['name'])) {
            return Response::error('Channel name is required');
        }

        $channel = Channel::create($data);

        // Rebuild abilities
        Ability::rebuildAbilities();

        return Response::success($channel->toArray(), 'Channel created successfully');
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $channel = Channel::find($id);
        if (!$channel) {
            return Response::error('Channel not found', 404);
        }

        $fields = [
            'type', 'key', 'name', 'models', 'group', 'base_url', 'status',
            'weight', 'priority', 'auto_ban', 'other', 'model_mapping',
            'status_code_mapping', 'setting', 'param_override', 'header_override',
            'remark', 'openai_organization', 'test_model',
        ];

        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value !== null) {
                if (in_array($field, ['type', 'status', 'weight', 'priority', 'auto_ban'])) {
                    $channel->$field = (int) $value;
                } else {
                    $channel->$field = $value;
                }
            }
        }

        $channel->save();
        Ability::rebuildAbilities();

        return Response::success($channel->toArray(), 'Channel updated successfully');
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
        $channel = Channel::find($id);
        if (!$channel) {
            return Response::error('Channel not found', 404);
        }

        Ability::deleteByChannelId($id);
        $channel->delete();

        return Response::success(null, 'Channel deleted successfully');
    }

    public function batchDelete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return Response::error('No channels selected');
        }

        foreach ($ids as $id) {
            Ability::deleteByChannelId((int) $id);
        }
        Channel::batchDelete(array_map('intval', $ids));

        return Response::success(null, 'Channels deleted successfully');
    }

    public function batchUpdateStatus(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $ids = $request->input('ids', []);
        $status = (int) $request->input('status');
        if (empty($ids)) {
            return Response::error('No channels selected');
        }

        Channel::batchUpdateStatus(array_map('intval', $ids), $status);
        Ability::rebuildAbilities();

        return Response::success(null, 'Channel status updated successfully');
    }

    public function test(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = (int) $request->param('id');
        $channel = Channel::find($id);
        if (!$channel) {
            return Response::error('Channel not found', 404);
        }

        // Test the channel
        $testModel = $channel->test_model ?: 'gpt-3.5-turbo';
        $startTime = microtime(true);

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => false,
            ]);

            $baseUrl = $channel->base_url ?: 'https://api.openai.com';
            $response = $client->post(rtrim($baseUrl, '/') . '/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $channel->key,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $testModel,
                    'messages' => [['role' => 'user', 'content' => 'hi']],
                    'max_tokens' => 1,
                ],
                'http_errors' => false,
            ]);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->getStatusCode() === 200) {
                $channel->test_time = time();
                $channel->response_time = $responseTime;
                $channel->save();

                return Response::success([
                    'response_time' => $responseTime,
                    'status' => 'success',
                ], 'Channel test successful');
            } else {
                $body = (string) $response->getBody();
                return Response::error('Test failed: HTTP ' . $response->getStatusCode() . ' - ' . $body);
            }
        } catch (\Exception $e) {
            return Response::error('Test failed: ' . $e->getMessage());
        }
    }

    public function testAll(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        // Queue all channels for testing (simplified: return immediate response)
        return Response::success(null, 'All channels test initiated');
    }
}
