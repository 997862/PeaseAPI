<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Option;
use NewApi\Models\Ability;

class ModelController
{
    // 支持的模型列表
    private array $modelList = [
        // OpenAI
        'gpt-4', 'gpt-4-turbo', 'gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo',
        'gpt-4-1106-preview', 'gpt-4-0125-preview',
        // Claude
        'claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307',
        // Gemini
        'gemini-pro', 'gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-2.0-flash',
        // 国内模型
        'qwen-turbo', 'qwen-plus', 'qwen-max', 'qwen-long', 'qwen-vl-max',
        'ERNIE-Bot', 'ERNIE-Bot-4', 'glm-4', 'glm-4-plus', 'glm-4v',
        'deepseek-chat', 'deepseek-coder', 'deepseek-reasoner',
        // 其他
        'llama-3-70b', 'llama-3-8b', 'mistral-large', 'mixtral-8x7b',
        'moonshot-v1-8k', 'moonshot-v1-32k', 'moonshot-v1-128k',
    ];

    public function list(Request $request): Response
    {
        $models = $this->getModelsWithConfig();
        return Response::success(['models' => $models]);
    }

    public function getConfig(Request $request): Response
    {
        $modelName = $request->param('model');
        $ratio = Option::get("Ratio_$modelName", '1');
        return Response::success([
            'model' => $modelName,
            'ratio' => (float) $ratio,
        ]);
    }

    public function updateConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $model = $request->input('model');
        $ratio = $request->input('ratio');

        if (empty($model) || $ratio === null) {
            return Response::error('Model and ratio are required');
        }

        Option::updateOption("Ratio_$model", (string) $ratio);
        return Response::success(null, 'Model config updated');
    }

    public function getRatios(Request $request): Response
    {
        $ratios = [];
        foreach ($this->modelList as $model) {
            $ratio = Option::get("Ratio_$model", '1');
            $ratios[$model] = (float) $ratio;
        }
        return Response::success($ratios);
    }

    public function updateRatios(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $ratios = $request->input('ratios', []);
        foreach ($ratios as $model => $ratio) {
            Option::updateOption("Ratio_$model", (string) $ratio);
        }
        return Response::success(null, 'Ratios updated successfully');
    }

    private function getModelsWithConfig(): array
    {
        $result = [];
        foreach ($this->modelList as $model) {
            $result[] = [
                'id' => $model,
                'name' => $model,
                'ratio' => (float) Option::get("Ratio_$model", '1'),
                'enabled' => true,
            ];
        }
        return $result;
    }

    public function test(Request $request): Response
    {
        $modelName = $request->input('model');
        if (empty($modelName)) {
            return Response::error('Model is required');
        }

        // Simple availability check
        $abilities = Ability::getChannelsForModel($modelName);
        $available = !empty($abilities);

        return Response::success([
            'model' => $modelName,
            'available' => $available,
            'channel_count' => count($abilities),
        ]);
    }
}
