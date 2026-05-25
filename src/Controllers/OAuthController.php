<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\OAuthBinding;
use NewApi\Models\Option;

class OAuthController
{
    public function github(Request $request): Response
    {
        $code = $request->query('code');
        if (empty($code)) {
            $clientId = Option::get('GitHubClientId');
            $redirectUri = Option::get('GitHubRedirectUri', rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/github/callback');
            $state = bin2hex(random_bytes(16));
            session_start();
            $_SESSION['oauth_state'] = $state;
            $url = "https://github.com/login/oauth/authorize?client_id=$clientId&redirect_uri=$redirectUri&state=$state";
            return Response::json(['url' => $url]);
        }
        return $this->handleGitHubCallback($code, $request);
    }

    private function handleGitHubCallback(string $code, Request $request): Response
    {
        $clientId = Option::get('GitHubClientId');
        $clientSecret = Option::get('GitHubClientSecret');
        
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://github.com/login/oauth/access_token', [
            'json' => ['client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code],
            'headers' => ['Accept' => 'application/json'],
        ]);
        
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'] ?? '';
        if (empty($accessToken)) return Response::error('Failed to get GitHub token');
        
        $userResponse = $client->get('https://api.github.com/user', ['headers' => ['Authorization' => "token $accessToken"]]);
        $githubUser = json_decode((string) $userResponse->getBody(), true);
        $githubId = $githubUser['id'] ?? '';
        if (empty($githubId)) return Response::error('Failed to get GitHub user info');
        
        return $this->handleOAuthLogin('github', $githubId, $githubUser['login'] ?? '', $githubUser['email'] ?? '');
    }

    public function google(Request $request): Response
    {
        $code = $request->query('code');
        if (empty($code)) {
            $clientId = Option::get('GoogleClientId');
            $redirectUri = Option::get('GoogleRedirectUri', 'https://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/google/callback');
            $state = bin2hex(random_bytes(16));
            session_start();
            $_SESSION['oauth_state'] = $state;
            $url = "https://accounts.google.com/o/oauth2/v2/auth?client_id=$clientId&redirect_uri=$redirectUri&state=$state&response_type=code&scope=openid%20email%20profile";
            return Response::json(['url' => $url]);
        }
        return $this->handleGoogleCallback($code, $request);
    }

    private function handleGoogleCallback(string $code, Request $request): Response
    {
        $clientId = Option::get('GoogleClientId');
        $clientSecret = Option::get('GoogleClientSecret');
        $redirectUri = Option::get('GoogleRedirectUri', 'https://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/google/callback');
        
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://oauth2.googleapis.com/token', [
            'json' => ['client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code, 'redirect_uri' => $redirectUri, 'grant_type' => 'authorization_code'],
        ]);
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'] ?? '';
        if (empty($accessToken)) return Response::error('Failed to get Google token');
        
        $userResponse = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', ['headers' => ['Authorization' => "Bearer $accessToken"]]);
        $googleUser = json_decode((string) $userResponse->getBody(), true);
        $googleId = $googleUser['id'] ?? '';
        if (empty($googleId)) return Response::error('Failed to get Google user info');
        
        return $this->handleOAuthLogin('google', $googleId, $googleUser['name'] ?? '', $googleUser['email'] ?? '');
    }

    public function qq(Request $request): Response
    {
        $code = $request->query('code');
        if (empty($code)) {
            $clientId = Option::get('QQClientId');
            $redirectUri = Option::get('QQRedirectUri', 'https://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/qq/callback');
            $state = bin2hex(random_bytes(16));
            session_start();
            $_SESSION['oauth_state'] = $state;
            $url = "https://graph.qq.com/oauth2.0/authorize?client_id=$clientId&redirect_uri=$redirectUri&state=$state&response_type=code&scope=get_user_info";
            return Response::json(['url' => $url]);
        }
        return $this->handleQQCallback($code, $request);
    }

    private function handleQQCallback(string $code, Request $request): Response
    {
        $clientId = Option::get('QQClientId');
        $clientSecret = Option::get('QQClientSecret');
        $redirectUri = Option::get('QQRedirectUri', 'https://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/qq/callback');
        
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=$clientId&client_secret=$clientSecret&code=$code&redirect_uri=" . urlencode($redirectUri));
        parse_str((string) $response->getBody(), $data);
        $accessToken = $data['access_token'] ?? '';
        if (empty($accessToken)) return Response::error('Failed to get QQ token');
        
        $openResponse = $client->get("https://graph.qq.com/oauth2.0/me?access_token=$accessToken");
        $openBody = (string) $openResponse->getBody();
        preg_match('/"openid":"(\w+)"/', $openBody, $matches);
        $qqId = $matches[1] ?? '';
        if (empty($qqId)) return Response::error('Failed to get QQ openid');
        
        $userInfo = $client->get("https://graph.qq.com/user/get_user_info?access_token=$accessToken&oauth_consumer_key=$clientId&openid=$qqId");
        $qqUser = json_decode((string) $userInfo->getBody(), true);
        
        return $this->handleOAuthLogin('qq', $qqId, $qqUser['nickname'] ?? '', '');
    }

    public function wechat(Request $request): Response
    {
        $code = $request->query('code');
        if (empty($code)) {
            $clientId = Option::get('WechatClientId');
            $redirectUri = Option::get('WechatRedirectUri', 'https://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/wechat/callback');
            $state = bin2hex(random_bytes(16));
            session_start();
            $_SESSION['oauth_state'] = $state;
            $url = "https://open.weixin.qq.com/connect/qrconnect?appid=$clientId&redirect_uri=" . urlencode($redirectUri) . "&state=$state&response_type=code&scope=snsapi_login";
            return Response::json(['url' => $url]);
        }
        return $this->handleWechatCallback($code, $request);
    }

    private function handleWechatCallback(string $code, Request $request): Response
    {
        $clientId = Option::get('WechatClientId');
        $clientSecret = Option::get('WechatClientSecret');
        
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$clientId&secret=$clientSecret&code=$code&grant_type=authorization_code");
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'] ?? '';
        $openId = $data['openid'] ?? '';
        if (empty($accessToken) || empty($openId)) return Response::error('Failed to get WeChat token');
        
        $userInfo = $client->get("https://api.weixin.qq.com/sns/userinfo?access_token=$accessToken&openid=$openId");
        $wechatUser = json_decode((string) $userInfo->getBody(), true);
        
        return $this->handleOAuthLogin('wechat', $openId, $wechatUser['nickname'] ?? '', '');
    }

    private function handleOAuthLogin(string $provider, string $providerId, string $displayName, string $email): Response
    {
        $binding = OAuthBinding::firstWhere(['provider' => $provider, 'provider_id' => $providerId]);
        
        if ($binding) {
            $user = User::find($binding->user_id);
        } else {
            $username = $provider . '_' . $providerId;
            if (User::usernameExists($username)) $username .= '_' . time();
            
            $user = User::create([
                'username' => $username,
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'display_name' => $displayName ?: $username,
                'role' => ROLE_COMMON_USER,
                'status' => USER_STATUS_ENABLED,
                'email' => $email,
                'quota' => (int) Option::get('NewUserQuota', 0),
                'group' => 'default',
            ]);
            $user->generateAffCode();
            
            OAuthBinding::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $providerId,
                'created_at' => time(),
            ]);
        }
        
        session_start();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        
        $token = $user->setAccessToken();
        
        return Response::success([
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role' => $user->role,
            'access_token' => $token,
        ], 'Login successful');
    }

    public function lark(Request $request): Response
    {
        return Response::error('Lark OAuth not configured');
    }
}