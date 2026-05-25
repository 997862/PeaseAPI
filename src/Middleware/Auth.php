<?php

namespace NewApi\Middleware;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Token;

class Auth
{
    public static function userAuth(): callable
    {
        return function (Request $request, callable $next): ?Response {
            session_start();
            $userId = $_SESSION['user_id'] ?? null;

            if ($userId === null) {
                $authHeader = $request->getHeader('Authorization');
                if ($authHeader) {
                    // 移除 "Bearer " 前缀
                    if (str_starts_with($authHeader, 'Bearer ')) {
                        $authHeader = substr($authHeader, 7);
                    }
                    
                    $token = Token::getByKey($authHeader);
                    if ($token) {
                        // IP 限制检查
                        if (!empty($token->ip_limit)) {
                            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
                            $allowedIps = array_map('trim', explode(',', $token->ip_limit));
                            if (!in_array($clientIp, $allowedIps) && !in_array('*', $allowedIps)) {
                                return Response::error('IP address not allowed', 403);
                            }
                        }
                        $request->setAttribute('user_id', $token->user_id);
                        $request->setAttribute('token', $token);
                        $request->setAttribute('token_id', $token->id);
                        return $next($request);
                    }

                    $user = User::getByAccessToken($authHeader);
                    if ($user && $user->status === USER_STATUS_ENABLED) {
                        $request->setAttribute('user_id', $user->id);
                        $request->setAttribute('user', $user);
                        return $next($request);
                    }
                }

                return Response::error('Unauthorized: not logged in', 401);
            }

            $user = User::find((int)$userId);
            if (!$user || $user->status !== USER_STATUS_ENABLED) {
                return Response::error('Unauthorized: user disabled', 401);
            }

            $request->setAttribute('user_id', $user->id);
            $request->setAttribute('user', $user);
            return $next($request);
        };
    }

    public static function adminAuth(): callable
    {
        return function (Request $request, callable $next): ?Response {
            session_start();
            $userId = $_SESSION['user_id'] ?? null;

            $user = null;
            if ($userId) {
                $user = User::find((int)$userId);
            } else {
                $authHeader = $request->getHeader('Authorization');
                if ($authHeader) {
                    // 移除 "Bearer " 前缀
                    if (str_starts_with($authHeader, 'Bearer ')) {
                        $authHeader = substr($authHeader, 7);
                    }
                    // 系统令牌检查
                    $systemToken = \NewApi\Models\SystemToken::validate($authHeader);
                    if ($systemToken && $systemToken->role >= ROLE_ADMIN_USER) {
                        $request->setAttribute('user_id', $systemToken->user_id ?: 0);
                        $request->setAttribute('user', $systemToken);
                        return $next($request);
                    }
                    $user = User::getByAccessToken($authHeader);
                }
            }

            if (!$user || $user->role < ROLE_ADMIN_USER) {
                return Response::error('Forbidden: admin access required', 403);
            }

            $request->setAttribute('user_id', $user->id);
            $request->setAttribute('user', $user);
            return $next($request);
        };
    }

    public static function tokenAuth(): callable
    {
        return function (Request $request, callable $next): ?Response {
            $apiKey = $request->getHeader('Authorization');
            if (!$apiKey) $apiKey = $request->getHeader('x-api-key');
            if (str_starts_with($apiKey, 'Bearer ')) $apiKey = substr($apiKey, 7);

            if (!$apiKey) {
                return Response::openaiError('You didn\'t provide an API key.', 'invalid_request_error', 401);
            }

            $token = Token::getByKey($apiKey);
            if (!$token) {
                return Response::openaiError('Incorrect API key provided.', 'invalid_request_error', 401);
            }

            // IP 限制
            if (!empty($token->ip_limit)) {
                $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
                $allowedIps = array_map('trim', explode(',', $token->ip_limit));
                if (!in_array($clientIp, $allowedIps) && !in_array('*', $allowedIps)) {
                    return Response::openaiError('IP address not allowed.', 'invalid_request_error', 403);
                }
            }

            if ($token->status === TOKEN_STATUS_DISABLED) {
                return Response::openaiError('This API key has been disabled.', 'invalid_request_error', 401);
            }
            if ($token->isExpired()) {
                return Response::openaiError('This API key has expired.', 'invalid_request_error', 401);
            }

            $user = User::find($token->user_id);
            if (!$user || $user->status !== USER_STATUS_ENABLED) {
                return Response::openaiError('Account disabled.', 'invalid_request_error', 401);
            }

            $request->setAttribute('user_id', $user->id);
            $request->setAttribute('user', $user);
            $request->setAttribute('token', $token);
            $request->setAttribute('token_id', $token->id);
            return $next($request);
        };
    }
}