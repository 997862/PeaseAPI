<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Token;
use NewApi\Models\Option;

class AuthController
{
    public function login(Request $request): Response
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (empty($username) || empty($password)) {
            return Response::error('Username and password are required');
        }

        if (!Option::getBool('PasswordLoginEnabled', true)) {
            return Response::error('Password login is disabled');
        }

        $user = User::verifyUser($username, $password);
        if (!$user) {
            return Response::error('Invalid username or password', 401);
        }

        // 后台登录权限拦截：仅允许角色 >= 2 (管理员/超管) 登录
        if ($user->role < 2) {
            return Response::error('后台仅限管理员登录，普通用户请前往用户中心', 403);
        }

        if ($user->status !== USER_STATUS_ENABLED) {
            return Response::error('User is disabled', 403);
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

        // 记录登录日志（用于公安取证）
        try {
            $db = \NewApi\Database\Connection::getInstance();
            $stmt = $db->prepare("
                INSERT INTO login_logs (user_id, username, login_ip, login_port, user_agent, login_time, status, login_type)
                VALUES (?, ?, ?, ?, ?, ?, 1, 'password')
            ");
            $stmt->execute([
                $user->id,
                $user->username,
                $clientIp,
                $clientPort,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $loginTime
            ]);
        } catch (\Exception $e) {
            error_log("Failed to record login log: " . $e->getMessage());
        }

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
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role' => $user->role,
            'status' => $user->status,
            'email' => $user->email,
            'quota' => $user->quota,
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
        $phone = $request->input('phone');
        $affCode = $request->input('aff_code');

        if (empty($username) || empty($password)) {
            return Response::error('Username and password are required');
        }

        // 邮箱必填
        if (empty($email)) {
            return Response::error('Email is required');
        }

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error('Invalid email format');
        }

        // 验证密码长度
        if (strlen($password) < 8 || strlen($password) > 20) {
            return Response::error('Password must be 8-20 characters');
        }

        // 验证用户名
        if (strlen($username) > 20) {
            return Response::error('Username too long (max 20 characters)');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return Response::error('Username can only contain letters, numbers, and underscores');
        }

        if (User::usernameExists($username)) {
            return Response::error('Username already exists');
        }

        if (User::emailExists($email)) {
            return Response::error('Email already exists');
        }

        // 如果提供了手机号，验证格式
        if (!empty($phone)) {
            if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
                return Response::error('Invalid phone number format');
            }

            // 如果启用了短信验证，需要验证验证码
            $smsEnabled = Option::getBool('SmsEnabled', false);
            if ($smsEnabled) {
                $smsCode = $request->input('sms_code');
                if (empty($smsCode)) {
                    return Response::error('SMS verification code is required');
                }

                $smsData = $_SESSION['sms_code_' . $phone] ?? null;
                if (!$smsData || time() > $smsData['expire'] || $smsData['code'] !== $smsCode) {
                    return Response::error('Invalid SMS verification code');
                }
                unset($_SESSION['sms_code_' . $phone]);
            }
        }

        // 获取注册IP
        $registrationIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $inviterId = null;
        if ($affCode) {
            $inviter = User::getByAffCode($affCode);
            if ($inviter) {
                $inviterId = $inviter->id;
            }
        }

        $user = User::create([
            'username' => $username,
            'password' => \NewApi\Utils\hash_password($password),
            'display_name' => $username,
            'role' => ROLE_COMMON_USER,
            'status' => USER_STATUS_ENABLED,
            'email' => $email,
            'phone' => $phone ?: '',
            'quota' => (int) Option::get('NewUserQuota', 0),
            'group' => 'default',
            'inviter_id' => $inviterId,
            'registration_ip' => $registrationIp,
        ]);

        if ($inviterId) {
            $inviter = User::find($inviterId);
            if ($inviter) {
                $inviter->aff_count++;
                $inviter->save();
            }
        }

        $user->generateAffCode();

        // 记录登录日志（注册也算一次登录）
        try {
            $db = \NewApi\Database\Connection::getInstance();
            $stmt = $db->prepare("
                INSERT INTO login_logs (user_id, username, login_ip, login_port, user_agent, login_time, status, login_type)
                VALUES (?, ?, ?, ?, ?, ?, 1, 'register')
            ");
            $stmt->execute([
                $user->id,
                $user->username,
                $registrationIp,
                (int)($_SERVER['REMOTE_PORT'] ?? 0),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                time()
            ]);
        } catch (\Exception $e) {
            error_log("Failed to record registration log: " . $e->getMessage());
        }

        return Response::success([
            'id' => $user->id,
            'username' => $user->username,
        ], 'Registration successful');
    }

    public function logout(Request $request): Response
    {
        session_start();
        session_destroy();
        return Response::success(null, 'Logged out successfully');
    }

    public function generateAccessToken(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user) {
            return Response::error('User not found', 404);
        }

        $token = $user->setAccessToken();
        return Response::success(['token' => $token], 'Access token generated');
    }

    public function getSelf(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            session_start();
            $userId = $_SESSION['user_id'] ?? null;
            session_write_close();
        }
        $user = User::find($userId);
        if (!$user) {
            return Response::error('User not found', 404);
        }

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
            'group' => $user->group,
            'aff_code' => $user->aff_code,
            'aff_count' => $user->aff_count,
            'aff_quota' => $user->aff_quota,
            'aff_history_quota' => $user->aff_history_quota,
            'inviter_id' => $user->inviter_id,
        ]);
    }

    public function updateSelf(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = User::find($userId);
        if (!$user) {
            return Response::error('User not found', 404);
        }

        // 用户名修改（需要验证密码以确保安全）
        $newUsername = $request->input('username');
        if ($newUsername !== null && $newUsername !== $user->username) {
            // 验证用户名格式
            if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                return Response::error('Username must be 3-20 characters');
            }
            // 只允许字母、数字、下划线
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
                return Response::error('Username can only contain letters, numbers, and underscores');
            }
            // 检查是否已存在
            if (User::usernameExists($newUsername)) {
                return Response::error('Username already exists');
            }
            // 必须提供旧密码才能修改用户名
            $verifyPassword = $request->input('verify_password');
            if (!$verifyPassword || !password_verify($verifyPassword, $user->password)) {
                return Response::error('Password verification required to change username');
            }
            $user->username = $newUsername;
        }

        // 显示名称修改
        $displayName = $request->input('display_name');
        if ($displayName !== null) {
            $user->display_name = substr($displayName, 0, 20);
        }

        // 邮箱修改
        $email = $request->input('email');
        if ($email !== null) {
            if (User::emailExists($email) && $user->email !== $email) {
                return Response::error('Email already in use');
            }
            $user->email = $email;
        }

        // 密码修改
        $oldPassword = $request->input('old_password');
        $newPassword = $request->input('new_password');
        if ($oldPassword && $newPassword) {
            if (!password_verify($oldPassword, $user->password)) {
                return Response::error('Old password is incorrect');
            }
            $user->password = \NewApi\Utils\hash_password($newPassword);
        }

        $user->save();

        // 更新 Session 中的用户名
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['username'] = $user->username;
        session_write_close();

        return Response::success(null, 'Profile updated');
    }
    public function publicLogin(Request $request): Response
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
            return Response::error('用户名或密码错误', 400);
        }

        if ($user->status !== USER_STATUS_ENABLED) {
            return Response::error('账号已被禁用', 403);
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $loginTime = time();
        $user->last_login_at = $loginTime;
        $user->last_login_ip = $clientIp;
        $user->save();

        $token = $user->setAccessToken();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        session_write_close();

        return Response::success([
            'access_token' => $token,
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
        ], '登录成功');
    }
}
