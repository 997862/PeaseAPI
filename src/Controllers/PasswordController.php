<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\User;
use NewApi\Models\Option;

class PasswordController
{
    public function reset(Request $request): Response
    {
        $email = $request->input('email');
        if (empty($email)) {
            return Response::error('Email is required');
        }
        
        $user = User::firstWhere('email', $email);
        if (!$user) {
            // Don't reveal if email exists
            return Response::success(null, 'If the email exists, a reset link has been sent');
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + 3600; // 1 hour
        
        // Store token in options (simplified, should use dedicated table in production)
        Option::set("password_reset_$token", json_encode([
            'user_id' => $user->id,
            'email' => $email,
            'expires_at' => $expiresAt,
        ]));
        
        // Send email (simplified)
        $this->sendResetEmail($email, $token);
        
        return Response::success(null, 'Reset link sent to your email');
    }

    public function confirm(Request $request): Response
    {
        $token = $request->input('token');
        $newPassword = $request->input('new_password');
        
        if (empty($token) || empty($newPassword)) {
            return Response::error('Token and new password are required');
        }
        
        $resetData = Option::get("password_reset_$token");
        if (empty($resetData)) {
            return Response::error('Invalid or expired token');
        }
        
        $data = json_decode($resetData, true);
        if ($data['expires_at'] < time()) {
            return Response::error('Token expired');
        }
        
        // Update password
        $user = User::find($data['user_id']);
        if ($user) {
            $user->password = \NewApi\Utils\hash_password($newPassword);
            $user->save();
            
            // Delete token
            Option::set("password_reset_$token", '');
        }
        
        return Response::success(null, 'Password reset successful');
    }

    private function sendResetEmail(string $email, string $token): void
    {
        $resetLink = rtrim(Option::get('FrontendBaseUrl', 'http://localhost:3000'), '/') . "/reset?token=$token";
        
        // Implement actual email sending here
        // For now, just log it
        \NewApi\Utils\log_info("Password reset link for $email: $resetLink");
    }
}
