<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\MailTemplate;
use NewApi\Models\User;
use NewApi\Models\Option;
use NewApi\Services\SmtpMailer;

class MailController
{
    // 获取 SMTP 配置（管理员）
    public function getSmtpConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        return Response::success([
            'SmtpEnabled' => Option::get('SmtpEnabled', 'false'),
            'SmtpHost' => Option::get('SmtpHost', ''),
            'SmtpPort' => Option::get('SmtpPort', '465'),
            'SmtpUseSsl' => Option::get('SmtpUseSsl', 'true'),
            'SmtpUsername' => Option::get('SmtpUsername', ''),
            'SmtpPassword' => Option::get('SmtpPassword', ''),
            'SmtpFromEmail' => Option::get('SmtpFromEmail', ''),
            'SmtpFromName' => Option::get('SmtpFromName', 'PeaseAPI'),
        ]);
    }

    // 保存 SMTP 配置（管理员）
    public function saveSmtpConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $config = $request->all();
        $fields = [
            'SmtpEnabled', 'SmtpHost', 'SmtpPort', 'SmtpUseSsl',
            'SmtpUsername', 'SmtpPassword', 'SmtpFromEmail', 'SmtpFromName',
        ];

        foreach ($fields as $field) {
            if (isset($config[$field])) {
                Option::set($field, $config[$field]);
            }
        }

        return Response::success(null, 'SMTP 配置保存成功');
    }

    // 测试 SMTP 连接
    public function testSmtp(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $config = $request->all();
        $mailer = new SmtpMailer();
        $result = $mailer->testConnection($config);

        if ($result['success']) {
            return Response::success(null, $result['message']);
        }
        return Response::error($result['message'], 400);
    }

    // 测试发送邮件
    public function testSendMail(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $to = $request->input('to');
        $subject = $request->input('subject', 'PeaseAPI 测试邮件');
        $templateSlug = $request->input('template_slug');

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return Response::error('请输入有效的邮箱地址');
        }

        $mailer = new SmtpMailer();

        if ($templateSlug) {
            // 使用模板发送
            $template = MailTemplate::findBySlug($templateSlug);
            if (!$template) {
                return Response::error('模板不存在');
            }
            $result = $template->sendTo($to, ['username' => '测试用户']);
        } else {
            // 发送简单测试邮件
            $body = '<html><body style="font-family:sans-serif;padding:40px;">
                <h2 style="color:#6366F1;">🫛 PeaseAPI 测试邮件</h2>
                <p>这是一封测试邮件，用于验证 SMTP 配置是否正确。</p>
                <p>发送时间：' . date('Y-m-d H:i:s') . '</p>
                <p style="color:#999;font-size:12px;">如果您收到此邮件，说明 SMTP 配置正确！</p>
            </body></html>';
            $result = $mailer->send($to, $subject, $body);
        }

        if ($result['success']) {
            return Response::success(null, '测试邮件已发送，请检查收件箱');
        }
        return Response::error($result['message'], 400);
    }

    // ========== 邮件模板管理 ==========

    // 获取模板列表
    public function listTemplates(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 20);
        $search = $request->input('search', '');

        $result = MailTemplate::searchPaginate($page, $perPage, $search);
        return Response::success($result);
    }

    // 获取所有模板（不分页）
    public function getAllTemplates(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $templates = MailTemplate::getAllTemplates();
        return Response::success($templates);
    }

    // 获取单个模板
    public function getTemplate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $template = MailTemplate::find((int)$id);
        if (!$template) {
            return Response::error('模板不存在', 404);
        }

        return Response::success($template);
    }

    // 创建模板
    public function createTemplate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $data = $request->all();
        $required = ['slug', 'name', 'subject', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return Response::error("缺少必填字段: {$field}");
            }
        }

        // 检查 slug 是否已存在
        $existing = MailTemplate::findBySlug($data['slug']);
        if ($existing) {
            return Response::error('模板标识已存在');
        }

        $template = new MailTemplate([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'description' => $data['description'] ?? '',
            'variables' => $data['variables'] ?? '[]',
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'is_system' => false,
        ]);

        $template->save();
        return Response::success($template, '模板创建成功');
    }

    // 更新模板
    public function updateTemplate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $template = MailTemplate::find((int)$id);
        if (!$template) {
            return Response::error('模板不存在', 404);
        }

        $data = $request->all();

        // 系统模板不允许修改 slug
        if ($template->is_system && isset($data['slug']) && $data['slug'] !== $template->slug) {
            return Response::error('系统模板不允许修改标识');
        }

        foreach (['slug', 'name', 'subject', 'content', 'description', 'variables'] as $field) {
            if (isset($data[$field])) {
                $template->$field = $data[$field];
            }
        }

        if (isset($data['is_active'])) {
            $template->is_active = (bool)$data['is_active'];
        }

        $template->save();
        return Response::success($template, '模板更新成功');
    }

    // 删除模板
    public function deleteTemplate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $template = MailTemplate::find((int)$id);
        if (!$template) {
            return Response::error('模板不存在', 404);
        }

        if ($template->is_system) {
            return Response::error('系统模板不允许删除');
        }

        $template->delete();
        return Response::success(null, '模板删除成功');
    }

    // 测试模板发送
    public function testTemplate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $template = MailTemplate::find((int)$id);
        if (!$template) {
            return Response::error('模板不存在', 404);
        }

        $to = $request->input('to');
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return Response::error('请输入有效的邮箱地址');
        }

        // 解析变量
        $variables = $request->input('variables', []);
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?: [];
        }

        $result = $template->sendTo($to, $variables);

        if ($result['success']) {
            return Response::success(null, '测试邮件已发送，请检查收件箱');
        }
        return Response::error($result['message'], 400);
    }

    // 预览模板渲染效果
    public function previewTemplate(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $id = $request->param('id');
        $template = MailTemplate::find((int)$id);
        if (!$template) {
            return Response::error('模板不存在', 404);
        }

        $variables = $request->input('variables', []);
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?: [];
        }

        $rendered = $template->render($variables);
        return Response::success($rendered);
    }
}
