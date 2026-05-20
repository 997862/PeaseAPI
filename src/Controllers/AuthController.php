<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Option;

class AuthController
{
    public function login(Request $request): Response
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (empty($username) || empty($password)) {
            return Response::error('用户名和密码不能为空');
        }

        if (!Option::getBool('PasswordLoginEnabled', true)) {
            return Response::error('密码登录已关闭');
        }

        $user = User::verifyUser($username, $password);
        if (!$user) {
            return Response::error('用户名或密码错误', 401);
        }

        // 后台登录权限拦截：仅允许角色 >= 2 (管理员/超管) 登录
        if ($user->role < 2) {
            return Response::error('后台仅限管理员登录，普通用户请前往用户中心', 403);
        }

        if ($user->status !== USER_STATUS_ENABLED) {
            return Response::error('账号已被禁用', 403);
        }

        // Check 2FA
        $twoFASecret = \NewApi\Models\TwoFASecret::getByUserId($user->id);
        if ($twoFASecret && $twoFASecret->enabled) {
            $otp = $request->input('otp');
            if (empty($otp)) {
                return Response::json([
                    'success' => true,
                    'data' => ['twoFARequired' => true],
                    'message' => '2FA verification required',
                ]);
            }
            // Verify OTP here
        }

        // 获取客户端信息
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $clientPort = (int)($_SERVER['REMOTE_PORT'] ?? 0);
        $loginTime = time();

        // 更新最后登录信息（IP + 时间）
        $user->last_login_at = $loginTime;
        $user->last_login_ip = $clientIp;
        $user->save();

        // 生成 JWT Token
        $token = $user->setAccessToken();

        // 写入 Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        $_SESSION['status'] = $user->status;
        session_write_close();

        return Response::success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400 * 7,
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role' => $user->role,
            'status' => $user->status,
            'email' => $user->email,
            'quota' => $user->quota,
            'used_quota' => $user->used_quota,
            'request_count' => $user->request_count,
        ], 'Login successful');
    }

    public function register(Request $request): Response
    {
        if (!Option::getBool('PasswordRegisterEnabled', true)) {
            return Response::error('Registration is disabled');
        }
        if (!Option::getBool('RegisterEnabled', true)) {
            return Response::error('Registration is disabled');
        }

        $username = $request->input('username');
        $password = $request->input('password');
        $email = $request->input('email');

        if (empty($username) || empty($password)) {
            return Response::error('用户名和密码不能为空');
        }

        // 邮箱必填
        if (empty($email)) {
            return Response::error('邮箱为必填项');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error('邮箱格式不正确');
        }

        if (User::usernameExists($username)) {
            return Response::error('用户名已存在');
        }

        if (User::emailExists($email)) {
            return Response::error('邮箱已被注册');
        }

        $registrationIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $user = User::create([
            'username' => $username,
            'password' => \NewApi\Utils\hash_password($password),
            'display_name' => $username,
            'role' => ROLE_COMMON_USER,
            'status' => USER_STATUS_ENABLED,
            'email' => $email,
            'quota' => (int) Option::get('NewUserQuota', 0),
            'group' => 'default',
            'registration_ip' => $registrationIp,
        ]);

        $user->generateAffCode();

        // 记录注册日志
        try {
            $db = \NewApi\Database\Connection::getInstance();
            $stmt = $db->prepare("INSERT INTO login_logs (user_id, username, login_ip, login_port, user_agent, login_time, status, login_type) VALUES (?, ?, ?, ?, ?, ?, 1, 'register')");
            $stmt->execute([
                $user->id,
                $user->username,
                $registrationIp,
                (int)($_SERVER['REMOTE_PORT'] ?? 0),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                time()
            ]);
        } catch (\Exception $e) {
            error_log("Registration log failed: " . $e->getMessage());
        }

        return Response::success([
            'id' => $user->id,
            'username' => $user->username,
        ], '注册成功');
    }

    public function getSelf(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);
        
        $user = User::find($userId);
        if (!$user) return Response::error('User not found', 404);

        return Response::success([
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role' => $user->role,
            'status' => $user->status,
            'email' => $user->email,
            'quota' => $user->quota,
            'used_quota' => $user->used_quota,
            'request_count' => $user->request_count,
        ]);
    }

    public function updateSelf(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $user = User::find($userId);
        if (!$user) return Response::error('User not found', 404);

        $displayName = $request->input('display_name');
        $email = $request->input('email');
        $newPassword = $request->input('new_password');

        if ($displayName) $user->display_name = $displayName;
        if ($email) $user->email = $email;
        if ($newPassword) {
            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $user->save();

        return Response::success(null, 'User updated successfully');
    }

    public function generateAccessToken(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $user = User::find($userId);
        if (!$user) return Response::error('User not found', 404);

        $token = $user->setAccessToken();
        return Response::success(['token' => $token], 'Access token generated');
    }
    
    public function logout(Request $request): Response
    {
        // Clear session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return Response::success(null, 'Logout successful');
    }
}
