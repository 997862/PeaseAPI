<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\Option;
use NewApi\Services\AliyunSms;
use NewApi\Services\TencentSms;

class SmsController
{
    private const IP_LIMIT_FILE = __DIR__ . '/../../storage/sms_ip_limits.json';

    // 发送验证码（注册/找回密码）
    public function sendCode(Request $request): Response
    {
        $phone = $request->input('phone');
        $type = $request->input('type', 'register');

        if (empty($phone)) {
            return Response::error('手机号不能为空');
        }

        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return Response::error('手机号格式不正确');
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // 1. IP 频率限制检查
        if (!$this->checkIpLimit($clientIp)) {
            return Response::error('该 IP 发送过于频繁，请稍后再试或联系客服');
        }

        // 2. 检查手机号发送频率 (60秒)
        $lastSend = $_SESSION['sms_last_send_' . $phone] ?? 0;
        $now = time();
        $timeDiff = $now - $lastSend;
        if ($timeDiff < 60) {
            return Response::error('请 ' . (60 - $timeDiff) . ' 秒后再试');
        }

        // 3. 生成验证码
        $code = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 4. 获取配置
        $aliyunEnabled = Option::getBool('SmsAliyunEnabled', false);
        $tencentEnabled = Option::getBool('SmsTencentEnabled', false);

        if (!$aliyunEnabled && !$tencentEnabled) {
            return Response::error('短信服务未启用');
        }

        // 5. 选择服务商策略：
        // - 如果两个都开启，优先使用与上次不同的服务商（轮询/容灾）
        // - 如果上次发送失败或无记录，默认使用阿里云
        $lastProvider = $_SESSION['sms_last_provider_' . $phone] ?? '';
        $selectedProvider = $this->selectProvider($aliyunEnabled, $tencentEnabled, $lastProvider);

        $result = $this->sendViaProvider($selectedProvider, $phone, $code);

        // 6. 如果首选失败，且另一个开启，尝试备用
        if (!$result['success']) {
            $fallbackProvider = ($selectedProvider === 'aliyun' && $tencentEnabled) ? 'tencent' : 
                                (($selectedProvider === 'tencent' && $aliyunEnabled) ? 'aliyun' : null);
            
            if ($fallbackProvider) {
                $result = $this->sendViaProvider($fallbackProvider, $phone, $code);
                if ($result['success']) {
                    $selectedProvider = $fallbackProvider;
                }
            }
        }

        if ($result['success']) {
            // 保存验证码到 session（5分钟有效）
            $_SESSION['sms_code_' . $phone] = [
                'code' => $code,
                'expire' => time() + 300,
                'type' => $type,
            ];
            $_SESSION['sms_last_send_' . $phone] = $now;
            $_SESSION['sms_last_provider_' . $phone] = $selectedProvider;

            // 更新 IP 计数
            $this->incrementIpLimit($clientIp);

            return Response::success([
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4), 
                'provider' => $selectedProvider
            ], '验证码已发送');
        }

        return Response::error($result['message'] ?? '发送失败');
    }

    // 选择服务商
    private function selectProvider(bool $aliyun, bool $tencent, string $last): string
    {
        if ($aliyun && $tencent) {
            // 如果上次用了阿里云，这次用腾讯云，反之亦然
            return ($last === 'tencent') ? 'aliyun' : 'aliyun'; // 默认阿里云，或者可以做随机
            // 更智能的轮询：
            // return ($last === 'aliyun') ? 'tencent' : 'aliyun';
        }
        return $aliyun ? 'aliyun' : 'tencent';
    }

    // 实际发送
    private function sendViaProvider(string $provider, string $phone, string $code): array
    {
        if ($provider === 'aliyun') {
            $sms = new AliyunSms();
            $templateCode = Option::get('SmsAliyunTemplateCode', '');
            return $sms->sendCode($phone, $code, $templateCode);
        } elseif ($provider === 'tencent') {
            $sms = new TencentSms();
            $templateId = Option::get('SmsTencentTemplateId', '');
            return $sms->sendCode($phone, $code, $templateId);
        }
        return ['success' => false, 'message' => '未知服务商'];
    }

    // IP 限制检查
    private function checkIpLimit(string $ip): bool
    {
        $limitMax = (int)Option::get('SmsIpHourlyLimit', 50);
        $limitHours = 1; // 固定按小时统计
        
        if (!file_exists(self::IP_LIMIT_FILE)) {
            return true;
        }
        
        $data = json_decode(file_get_contents(self::IP_LIMIT_FILE), true) ?: [];
        $now = time();

        if (isset($data[$ip])) {
            $record = $data[$ip];
            // 检查是否超过 1 小时
            if ($now - $record['start_time'] > $limitHours * 3600) {
                // 重置计数
                unset($data[$ip]);
                file_put_contents(self::IP_LIMIT_FILE, json_encode($data));
                return true;
            }
            // 检查次数
            if ($record['count'] >= $limitMax) {
                return false;
            }
        }
        return true;
    }

    // 增加 IP 计数
    private function incrementIpLimit(string $ip): void
    {
        $limitHours = 1;
        $data = file_exists(self::IP_LIMIT_FILE) ? json_decode(file_get_contents(self::IP_LIMIT_FILE), true) ?: [] : [];
        $now = time();

        if (!isset($data[$ip]) || ($now - $data[$ip]['start_time'] > $limitHours * 3600)) {
            $data[$ip] = ['count' => 1, 'start_time' => $now];
        } else {
            $data[$ip]['count']++;
        }
        
        file_put_contents(self::IP_LIMIT_FILE, json_encode($data));
    }

    // 验证验证码
    public function verifyCode(Request $request): Response
    {
        $phone = $request->input('phone');
        $code = $request->input('code');
        $type = $request->input('type', 'register');

        if (empty($phone) || empty($code)) {
            return Response::error('手机号和验证码不能为空');
        }

        $smsData = $_SESSION['sms_code_' . $phone] ?? null;
        if (!$smsData) {
            return Response::error('请先获取验证码');
        }

        if (time() > $smsData['expire']) {
            unset($_SESSION['sms_code_' . $phone]);
            return Response::error('验证码已过期');
        }

        if ($smsData['code'] !== $code) {
            return Response::error('验证码错误');
        }

        if ($smsData['type'] !== $type) {
            return Response::error('验证码类型不匹配');
        }

        // 验证成功，清除验证码
        unset($_SESSION['sms_code_' . $phone]);

        return Response::success(['phone' => $phone], '验证成功');
    }

    // 获取短信配置（管理员）
    public function getConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        return Response::success([
            'SmsAliyunEnabled' => Option::get('SmsAliyunEnabled', 'false'),
            'SmsAliyunAccessKeyId' => Option::get('SmsAliyunAccessKeyId', ''),
            'SmsAliyunAccessKeySecret' => Option::get('SmsAliyunAccessKeySecret', ''),
            'SmsAliyunSignName' => Option::get('SmsAliyunSignName', ''),
            'SmsAliyunTemplateCode' => Option::get('SmsAliyunTemplateCode', ''),
            'SmsTencentEnabled' => Option::get('SmsTencentEnabled', 'false'),
            'SmsTencentSecretId' => Option::get('SmsTencentSecretId', ''),
            'SmsTencentSecretKey' => Option::get('SmsTencentSecretKey', ''),
            'SmsTencentSdkAppId' => Option::get('SmsTencentSdkAppId', ''),
            'SmsTencentSignName' => Option::get('SmsTencentSignName', ''),
            'SmsTencentTemplateId' => Option::get('SmsTencentTemplateId', ''),
            'SmsIpHourlyLimit' => Option::get('SmsIpHourlyLimit', '50')
        ]);
    }

    // 保存短信配置（管理员）
    public function saveConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $config = $request->all();
        $fields = [
            'SmsAliyunEnabled', 'SmsAliyunAccessKeyId', 'SmsAliyunAccessKeySecret', 'SmsAliyunSignName', 'SmsAliyunTemplateCode',
            'SmsTencentEnabled', 'SmsTencentSecretId', 'SmsTencentSecretKey', 'SmsTencentSdkAppId', 'SmsTencentSignName', 'SmsTencentTemplateId',
            'SmsIpHourlyLimit'
        ];

        foreach ($fields as $field) {
            if (isset($config[$field])) {
                Option::set($field, $config[$field]);
            }
        }

        return Response::success(null, '配置保存成功');
    }

    // 测试发送短信（管理员专用）
    public function testSend(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        $user = \NewApi\Models\User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $phone = $request->input('phone');
        $provider = $request->input('provider'); // 'aliyun' or 'tencent'

        if (empty($phone)) {
            return Response::error('手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return Response::error('手机号格式不正确');
        }

        if (!in_array($provider, ['aliyun', 'tencent'])) {
            return Response::error('无效的服务商，请选择 aliyun 或 tencent');
        }

        // 生成测试验证码
        $code = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 使用传入的配置或数据库配置
        $config = $request->input('config', []);
        
        // 保存临时配置用于测试
        $oldOptions = [];
        $tempOptions = [];
        
        if ($provider === 'aliyun') {
            $keys = ['SmsAliyunAccessKeyId', 'SmsAliyunAccessKeySecret', 'SmsAliyunSignName', 'SmsAliyunTemplateCode'];
            foreach ($keys as $key) {
                $oldOptions[$key] = Option::get($key, '');
                if (isset($config[$key]) && $config[$key] !== '') {
                    $tempOptions[$key] = $config[$key];
                    Option::set($key, $config[$key]);
                }
            }
        } else {
            $keys = ['SmsTencentSecretId', 'SmsTencentSecretKey', 'SmsTencentSdkAppId', 'SmsTencentSignName', 'SmsTencentTemplateId'];
            foreach ($keys as $key) {
                $oldOptions[$key] = Option::get($key, '');
                if (isset($config[$key]) && $config[$key] !== '') {
                    $tempOptions[$key] = $config[$key];
                    Option::set($key, $config[$key]);
                }
            }
        }

        // 执行发送
        $result = $this->sendViaProvider($provider, $phone, $code);

        // 恢复原始配置
        foreach ($oldOptions as $key => $value) {
            if (isset($tempOptions[$key])) {
                Option::set($key, $value);
            }
        }

        if ($result['success']) {
            return Response::success([
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                'provider' => $provider,
                'code' => $code // 测试模式返回验证码，生产环境不应返回
            ], '测试短信已发送，请检查手机');
        }

        return Response::error($result['message'] ?? '测试发送失败');
    }
}
