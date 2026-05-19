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
            // Redirect to GitHub OAuth
            $clientId = Option::get('GitHubClientId');
            $redirectUri = Option::get('GitHubRedirectUri', rtrim($_SERVER['HTTP_HOST'], '/') . '/api/oauth/github/callback');
            $state = bin2hex(random_bytes(16));
            
            session_start();
            $_SESSION['oauth_state'] = $state;
            
            $url = "https://github.com/login/oauth/authorize?client_id=$clientId&redirect_uri=$redirectUri&state=$state";
            return Response::json(['url' => $url]);
        }
        
        // Handle callback
        return $this->handleGitHubCallback($code, $request);
    }

    private function handleGitHubCallback(string $code, Request $request): Response
    {
        $clientId = Option::get('GitHubClientId');
        $clientSecret = Option::get('GitHubClientSecret');
        
        // Exchange code for token
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://github.com/login/oauth/access_token', [
            'json' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);
        
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'] ?? '';
        
        if (empty($accessToken)) {
            return Response::error('Failed to get GitHub token');
        }
        
        // Get user info
        $userResponse = $client->get('https://api.github.com/user', [
            'headers' => ['Authorization' => "token $accessToken"],
        ]);
        
        $githubUser = json_decode((string) $userResponse->getBody(), true);
        $githubId = $githubUser['id'] ?? '';
        
        if (empty($githubId)) {
            return Response::error('Failed to get GitHub user info');
        }
        
        // Check existing binding
        $binding = OAuthBinding::firstWhere(['provider' => 'github', 'provider_id' => $githubId]);
        
        if ($binding) {
            $user = User::find($binding->user_id);
        } else {
            // Create new user
            $username = 'github_' . $githubId;
            if (User::usernameExists($username)) {
                $username .= '_' . time();
            }
            
            $user = User::create([
                'username' => $username,
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'display_name' => $githubUser['login'] ?? $username,
                'role' => ROLE_COMMON_USER,
                'status' => USER_STATUS_ENABLED,
                'email' => $githubUser['email'] ?? '',
                'github_id' => $githubId,
                'quota' => (int) Option::get('NewUserQuota', 0),
                'group' => 'default',
            ]);
            $user->generateAffCode();
            
            OAuthBinding::create([
                'user_id' => $user->id,
                'provider' => 'github',
                'provider_id' => $githubId,
                'created_at' => time(),
            ]);
        }
        
        // Login
        session_start();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        
        return Response::success([
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role' => $user->role,
        ], 'Login successful');
    }

    public function lark(Request $request): Response
    {
        // Similar to GitHub OAuth but for Lark/Feishu
        return Response::error('Lark OAuth not configured');
    }
}
