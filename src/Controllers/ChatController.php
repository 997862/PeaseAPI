<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Token;
use NewApi\Models\Log;

class ChatController
{
    public function index(Request $request): Response
    {
        return Response::success(null, 'Chat interface');
    }

    public function sendMessage(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        
        $user = User::find($userId);
        if (!$user || $user->status !== USER_STATUS_ENABLED) {
            return Response::error('User disabled', 403);
        }

        $model = $request->input('model', 'gpt-3.5-turbo');
        $messages = $request->input('messages', []);
        $stream = (bool) $request->input('stream', false);

        if (empty($messages)) {
            return Response::error('Messages are required');
        }

        // Check quota
        if ($user->quota <= 0) {
            return Response::error('Insufficient quota');
        }

        // Find available channel
        $channel = $this->findAvailableChannel($model);
        if (!$channel) {
            return Response::error('No available channel for model: ' . $model);
        }

        // Forward to upstream
        return $this->forwardToUpstream($request, $channel, $model, $messages, $stream, $user);
    }

    private function findAvailableChannel(string $model): ?array
    {
        // Query channels that support this model and are enabled
        $db = \NewApi\Database\Connection::getInstance();
        $sql = "SELECT * FROM channels WHERE status = 1 AND models LIKE ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(["%$model%"]);
        return $stmt->fetch() ?: null;
    }

    private function forwardToUpstream(Request $request, array $channel, string $model, array $messages, bool $stream, User $user): Response
    {
        $client = new \GuzzleHttp\Client([
            'timeout' => 300,
            'connect_timeout' => 30,
            'verify' => false,
        ]);

        $baseUrl = $channel['base_url'] ?: 'https://api.openai.com';
        $url = rtrim($baseUrl, '/') . '/v1/chat/completions';

        $body = [
            'model' => $model,
            'messages' => $messages,
            'stream' => $stream,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $channel['key'],
            'Content-Type' => 'application/json',
        ];

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $body,
                'stream' => $stream,
            ]);

            if ($stream) {
                return $this->handleStreamResponse($response, $user, $channel, $model);
            }

            $data = json_decode((string) $response->getBody(), true);
            
            // Log and deduct quota
            $this->logAndDeduct($user, $channel, $model, $data);
            
            return Response::json($data, $response->getStatusCode());
        } catch (\Exception $e) {
            return Response::error('Upstream error: ' . $e->getMessage(), 502);
        }
    }

    private function handleStreamResponse($response, User $user, array $channel, string $model): Response
    {
        $resp = new Response();
        $resp->withHeader('Content-Type', 'text/event-stream');
        $resp->withHeader('Cache-Control', 'no-cache');
        $resp->withHeader('Connection', 'keep-alive');
        
        $stream = $response->getBody();
        $content = '';
        
        $resp->stream(function () use ($stream, &$content, $user, $channel, $model) {
            while (!$stream->eof()) {
                $line = $stream->read(1024);
                if ($line) {
                    $content .= $line;
                    yield $line;
                }
            }
            // Log after stream completes
        });
        
        return $resp;
    }

    private function logAndDeduct(User $user, array $channel, string $model, array $data): void
    {
        $usage = $data['usage'] ?? [];
        $totalTokens = $usage['total_tokens'] ?? 0;
        $quota = (int)($totalTokens * QUOTA_PER_UNIT / 1000);
        
        // Deduct quota
        $user->quota = max(0, $user->quota - $quota);
        $user->used_quota += $quota;
        $user->request_count++;
        $user->save();
        
        // Create log
        try {
            Log::create([
                'user_id' => $user->id,
                'channel_id' => $channel['id'],
                'model_name' => $model,
                'quota' => $quota,
                'content' => substr(json_encode($data), 0, 1000),
                'created_at' => time(),
                'type' => LOG_TYPE_TEXT,
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $totalTokens,
            ]);
        } catch (\Exception $e) {
            // Log error silently
        }
    }
}
