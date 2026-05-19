<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Token;
use NewApi\Models\Channel;
use NewApi\Models\Ability;
use NewApi\Models\Option;
use NewApi\Models\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class RelayController
{
    private array $supportedChannels = [];
    private int $currentChannelIndex = 0;

    public function relay(Request $request, string $relayMode): Response
    {
        $startTime = microtime(true);
        $token = $request->getAttribute('token');
        $user = $request->getAttribute('user');

        // Get model from request body
        $body = $request->getBody();
        $model = $body['model'] ?? '';

        if (empty($model)) {
            return Response::openaiError('model is required', 'invalid_request_error', 400);
        }

        // Get group from token or user
        $group = $token->getGroup() ?: $user->group ?: 'default';

        // Check token model limits
        $modelLimit = $token->getModelLimit();
        if ($modelLimit !== null && !empty($modelLimit)) {
            $allowed = false;
            foreach ($modelLimit as $allowedModel) {
                if ($allowedModel === $model || str_starts_with($model, $allowedModel)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return Response::openaiError(
                    "Model $model is not allowed by this API key",
                    'invalid_request_error', 403
                );
            }
        }

        // Check quota
        if ($token->remain_quota <= 0 && !$token->unlimited_quota) {
            return Response::openaiError(
                'Insufficient quota. Please contact your administrator.',
                'insufficient_quota', 429, null, 'insufficient_quota'
            );
        }

        // Find channel
        $channel = $this->findChannel($model, $group);
        if (!$channel) {
            return Response::openaiError(
                "Model $model is not available. Please contact your administrator.",
                'invalid_request_error', 404, null, 'model_not_found'
            );
        }

        // Build request to upstream
        $upstreamResponse = $this->sendToUpstream($request, $channel, $model);

        if ($upstreamResponse instanceof Response) {
            return $upstreamResponse;
        }

        // Handle streaming response
        $isStream = $body['stream'] ?? false;

        if ($isStream) {
            return $this->handleStreamResponse($request, $upstreamResponse, $channel, $token, $user, $model, $startTime);
        }

        // Handle non-streaming response
        return $this->handleNormalResponse($request, $upstreamResponse, $channel, $token, $user, $model, $startTime);
    }

    private function findChannel(string $model, string $group): ?array
    {
        $abilities = Ability::getChannelsForModel($model, $group);

        if (empty($abilities)) {
            // Try without group
            $abilities = Ability::getChannelsForModel($model);
        }

        if (empty($abilities)) {
            return null;
        }

        // Select by priority and weight
        $selected = $abilities[0];

        // Handle multi-key
        if (!empty($selected['key'])) {
            $keys = explode("\n", trim($selected['key']));
            if (count($keys) > 1) {
                $selected['current_key'] = $keys[array_rand($keys)];
            } else {
                $selected['current_key'] = $keys[0];
            }
        } else {
            $selected['current_key'] = '';
        }

        return $selected;
    }

    private function sendToUpstream(Request $request, array $channel, string $model): Response|\GuzzleHttp\Promise\PromiseInterface
    {
        $client = new Client([
            'timeout' => (int)(getenv('STREAMING_TIMEOUT') ?: 300),
            'connect_timeout' => 30,
            'verify' => false,
        ]);

        // Build upstream URL
        $baseUrl = $channel['base_url'] ?: $this->getDefaultBaseUrl($channel['type']);
        $path = $request->getPath();

        // Map path based on channel type
        $upstreamPath = $this->mapPath($path, $channel['type']);
        $url = rtrim($baseUrl, '/') . $upstreamPath;

        // Build headers
        $headers = [
            'Content-Type' => 'application/json',
        ];

        // Set auth header based on channel type
        switch ($channel['type']) {
            case CHANNEL_TYPE_OPENAI:
                $headers['Authorization'] = 'Bearer ' . ($channel['current_key'] ?: $channel['key']);
                if (!empty($channel['openai_organization'])) {
                    $headers['OpenAI-Organization'] = $channel['openai_organization'];
                }
                break;
            case CHANNEL_TYPE_CLAUDE:
            case CHANNEL_TYPE_AWS_CLAUDE:
                $headers['x-api-key'] = $channel['current_key'] ?: $channel['key'];
                $headers['anthropic-version'] = '2023-06-01';
                break;
            case CHANNEL_TYPE_GEMINI:
                // Gemini uses query parameter for API key
                $separator = strpos($url, '?') !== false ? '&' : '?';
                $url .= $separator . 'key=' . ($channel['current_key'] ?: $channel['key']);
                break;
            default:
                $headers['Authorization'] = 'Bearer ' . ($channel['current_key'] ?: $channel['key']);
        }

        // Apply model mapping
        $body = $request->getBody();
        $modelMapping = json_decode($channel['model_mapping'] ?? '', true);
        if ($modelMapping && isset($modelMapping[$body['model']])) {
            $body['model'] = $modelMapping[$body['model']];
        }

        // Handle request body modifications
        $body = $this->modifyRequestBody($body, $channel);

        $options = [
            'headers' => $headers,
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'stream' => ($body['stream'] ?? false),
            'http_errors' => false,
        ];

        try {
            $response = $client->request($request->getMethod(), $url, $options);
            return $response;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return Response::openaiError(
                'Connection failed: ' . $e->getMessage(),
                'api_error', 502
            );
        } catch (\Exception $e) {
            return Response::openaiError(
                'Upstream error: ' . $e->getMessage(),
                'api_error', 500
            );
        }
    }

    private function handleNormalResponse(Request $request, ResponseInterface $upstreamResponse, array $channel, Token $token, User $user, string $model, float $startTime): Response
    {
        $statusCode = $upstreamResponse->getStatusCode();
        $body = (string) $upstreamResponse->getBody();

        // Log the request
        $this->logRequest($user->id, $channel['id'], $model, $body, $startTime);

        // Deduct quota based on response
        $responseData = json_decode($body, true);
        if (isset($responseData['usage'])) {
            $totalTokens = $responseData['usage']['total_tokens'] ?? 0;
            $quota = $this->calculateQuota($model, $totalTokens);
            $token->consumeQuota($quota);
        }

        // Build response
        $response = Response::json($responseData, $statusCode);

        // Copy relevant headers
        foreach ($upstreamResponse->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['content-type', 'x-request-id', 'cf-ray'])) {
                $response->withHeader($name, implode(', ', $values));
            }
        }

        return $response;
    }

    private function handleStreamResponse(Request $request, ResponseInterface $upstreamResponse, array $channel, Token $token, User $user, string $model, float $startTime): Response
    {
        $response = new Response();
        $response->withHeader('Content-Type', 'text/event-stream');
        $response->withHeader('Cache-Control', 'no-cache');
        $response->withHeader('Connection', 'keep-alive');
        $response->withHeader('X-Accel-Buffering', 'no');

        $stream = $upstreamResponse->getBody();
        $totalContent = '';
        $promptTokens = 0;
        $completionTokens = 0;

        $response->stream(function () use ($stream, &$totalContent, &$promptTokens, &$completionTokens, $user, $channel, $model, $startTime, $token) {
            while (!$stream->eof()) {
                $line = $stream->read(1024);
                if ($line) {
                    $totalContent .= $line;
                    yield $line;
                }
            }

            // Extract usage from the last data line
            $lines = explode("\n", $totalContent);
            foreach (array_reverse($lines) as $line) {
                if (str_starts_with(trim($line), 'data: ')) {
                    $data = json_decode(substr(trim($line), 6), true);
                    if ($data && isset($data['usage'])) {
                        $promptTokens = $data['usage']['prompt_tokens'] ?? 0;
                        $completionTokens = $data['usage']['completion_tokens'] ?? 0;
                    }
                    break;
                }
            }

            // Deduct quota
            $totalTokens = $promptTokens + $completionTokens;
            if ($totalTokens > 0) {
                $quota = $this->calculateQuota($model, $totalTokens);
                $token->consumeQuota($quota);
            }

            // Log
            $this->logRequest($user->id, $channel['id'], $model, $totalContent, $startTime, true);
        });

        return $response;
    }

    private function calculateQuota(string $model, int $tokens): int
    {
        // Default: $0.002 per 1K tokens = 500000 quota per 1K tokens
        $ratio = (float) Option::get("Ratio_$model", '1');
        return (int) ($tokens * QUOTA_PER_UNIT / 1000 * $ratio);
    }

    private function logRequest(int $userId, int $channelId, string $model, string $content, float $startTime, bool $isStream = false): void
    {
        $quota = 0;
        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        $responseData = json_decode($content, true);
        if ($responseData && isset($responseData['usage'])) {
            $promptTokens = $responseData['usage']['prompt_tokens'] ?? 0;
            $completionTokens = $responseData['usage']['completion_tokens'] ?? 0;
            $totalTokens = $responseData['usage']['total_tokens'] ?? ($promptTokens + $completionTokens);
            $quota = $this->calculateQuota($model, $totalTokens);
        }

        try {
            Log::create([
                'user_id' => $userId,
                'channel_id' => $channelId,
                'model_name' => $model,
                'quota' => $quota,
                'content' => substr($content, 0, 2000),
                'request_id' => \NewApi\Utils\get_request_id(),
                'created_at' => time(),
                'type' => LOG_TYPE_TEXT,
                'is_stream' => $isStream,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
            ]);
        } catch (\Exception $e) {
            \NewApi\Utils\log_error('Failed to create log: ' . $e->getMessage());
        }
    }

    private function getDefaultBaseUrl(int $type): string
    {
        return match ($type) {
            CHANNEL_TYPE_OPENAI => 'https://api.openai.com',
            CHANNEL_TYPE_CLAUDE => 'https://api.anthropic.com',
            CHANNEL_TYPE_GEMINI => 'https://generativelanguage.googleapis.com',
            CHANNEL_TYPE_AZURE => '',
            CHANNEL_TYPE_ALI => 'https://dashscope.aliyuncs.com',
            CHANNEL_TYPE_ZHIPU => 'https://open.bigmodel.cn',
            CHANNEL_TYPE_BAIDU => 'https://aip.baidubce.com',
            CHANNEL_TYPE_DEEPSEEK => 'https://api.deepseek.com',
            CHANNEL_TYPE_MOONSHOT => 'https://api.moonshot.cn',
            CHANNEL_TYPE_SILICONFLOW => 'https://api.siliconflow.cn',
            default => 'https://api.openai.com',
        };
    }

    private function mapPath(string $path, int $type): string
    {
        return match ($type) {
            CHANNEL_TYPE_GEMINI => str_replace('/v1/', '/v1beta/', $path),
            default => $path,
        };
    }

    private function modifyRequestBody(array $body, array $channel): array
    {
        // Apply channel-specific parameter overrides
        $overrides = json_decode($channel['param_override'] ?? '{}', true);
        if ($overrides) {
            foreach ($overrides as $key => $value) {
                $body[$key] = $value;
            }
        }

        return $body;
    }

    public function listModels(Request $request): Response
    {
        $token = $request->getAttribute('token');
        $user = $request->getAttribute('user');
        $group = $token->getGroup() ?: $user->group ?: 'default';

        $modelLimit = $token->getModelLimit();

        $models = Ability::getModelsByGroup($group);

        // Filter by token model limit
        if ($modelLimit !== null && !empty($modelLimit)) {
            $models = array_filter($models, fn($m) => in_array($m, $modelLimit));
        }

        $modelData = [];
        foreach ($models as $modelName) {
            $modelData[] = [
                'id' => $modelName,
                'object' => 'model',
                'created' => time(),
                'owned_by' => 'new-api',
            ];
        }

        return Response::json([
            'object' => 'list',
            'data' => $modelData,
        ]);
    }

    public function retrieveModel(Request $request, string $model): Response
    {
        return Response::json([
            'id' => $model,
            'object' => 'model',
            'created' => time(),
            'owned_by' => 'new-api',
        ]);
    }
}
