<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Channel;
use NewApi\Models\Option;
use NewApi\Models\Token;
use NewApi\Models\Ability;

class SystemController
{
    public function getStatus(Request $request): Response
    {
        $userCount = User::count();
        $tokenCount = Token::count();
        $channelCount = Channel::count();
        $channelStatusCounts = Channel::countByStatus();

        return Response::success([
            'name' => SYSTEM_NAME,
            'version' => APP_VERSION,
            'start_time' => time(),
            'email' => 'test@example.com',
            'github_login' => Option::getBool('GitHubOAuthEnabled', false),
            'github_oauth_enabled' => Option::getBool('GitHubOAuthEnabled', false),
            'discord_login' => Option::getBool('DiscordOAuthEnabled', false),
            'oidc_login' => false,
            'linux_do_login' => false,
            'wechat_login' => false,
            'telegram_oauth' => false,
            'turnstile_check' => false,
            'register_enabled' => Option::getBool('RegisterEnabled', true),
            'password_login_enabled' => Option::getBool('PasswordLoginEnabled', true),
            'password_register_enabled' => Option::getBool('PasswordRegisterEnabled', true),
            'announcement' => Option::get('Announcement', ''),
            'quota_per_unit' => QUOTA_PER_UNIT,
            'display_in_currency_enabled' => true,
            'display_token_stat_enabled' => true,
            'top_up_link' => Option::get('TopUpLink', ''),
            'chat_link' => Option::get('ChatLink', ''),
            'default_theme' => Option::get('DefaultTheme', 'default'),
            'user_count' => $userCount,
            'token_count' => $tokenCount,
            'channel_count' => $channelCount,
        ]);
    }

    public function getNotice(Request $request): Response
    {
        $notice = Option::get('Notice', '');
        return Response::success([
            'content' => $notice,
        ]);
    }

    public function getSetup(Request $request): Response
    {
        $setup = Option::get('SetupCompleted');
        return Response::success([
            'setup' => $setup === 'true',
        ]);
    }

    public function postSetup(Request $request): Response
    {
        if (Option::get('SetupCompleted') === 'true') {
            return Response::error('System already initialized');
        }

        $username = $request->input('username', 'root');
        $password = $request->input('password');
        $email = $request->input('email', '');

        if (empty($password)) {
            return Response::error('Password is required');
        }

        // Create root user
        $user = User::create([
            'username' => $username,
            'password' => \NewApi\Utils\hash_password($password),
            'display_name' => $username,
            'role' => ROLE_ROOT_USER,
            'status' => USER_STATUS_ENABLED,
            'email' => $email,
            'quota' => 100000000,
        ]);
        $user->generateAffCode();

        Option::set('SetupCompleted', 'true');

        return Response::success(null, 'Setup completed successfully');
    }

    public function searchModels(Request $request): Response
    {
        $keyword = $request->query('keyword', '');
        $models = Ability::getAllModels();

        if ($keyword) {
            $models = array_filter($models, fn($m) => stripos($m, $keyword) !== false);
        }

        return Response::success($models);
    }

    public function getRatioConfig(Request $request): Response
    {
        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->query("SELECT `key`, `value` FROM options WHERE `key` LIKE 'Ratio_%'");
        $ratios = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $model = str_replace('Ratio_', '', $row['key']);
            $ratios[$model] = (float) $row['value'];
        }
        return Response::success($ratios);
    }

    public function getUserAgreement(Request $request): Response
    {
        return Response::success([
            'content' => Option::get('UserAgreement', ''),
        ]);
    }

    public function getPrivacyPolicy(Request $request): Response
    {
        return Response::success([
            'content' => Option::get('PrivacyPolicy', ''),
        ]);
    }

    public function getIpLocation(Request $request): Response
    {
        $ips = $request->input('ips', []);
        $singleIp = $request->input('ip');
        if (!empty($singleIp)) {
            $ips = [$singleIp];
        }

        if (empty($ips)) {
            return Response::error('IP is required');
        }

        $locations = [];
        foreach ($ips as $ip) {
            // 使用淘宝 IP 库（免费，无需 key）
            $url = "https://ip.taobao.com/outGetIpInfo?ip=" . urlencode($ip) . "&accessKey=alibaba-inc";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            if ($data && $data['code'] == 0 && isset($data['data'])) {
                $d = $data['data'];
                $location = ($d['country'] ?? '') . ($d['region'] ?? '') . ($d['city'] ?? '');
                $locations[$ip] = $location ?: $ip;
            } else {
                $locations[$ip] = $ip;
            }
        }

        return Response::success($locations);
    }

}
