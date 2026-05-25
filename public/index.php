<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Session configuration - must be before any output
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '86400');

use NewApi\Core\Router;
use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Middleware\Auth;
use NewApi\Middleware\CORS;
use NewApi\Middleware\RateLimit;
use NewApi\Middleware\Logger;
use NewApi\Controllers\AuthController;
use NewApi\Controllers\ChannelController;
use NewApi\Controllers\TokenController;
use NewApi\Controllers\UserController;
use NewApi\Controllers\SystemController;
use NewApi\Controllers\RelayController;
use NewApi\Controllers\LogController;
use NewApi\Controllers\OptionController;
use NewApi\Controllers\RedemptionController;
use NewApi\Controllers\TopupController;
use NewApi\Controllers\GroupController;
use NewApi\Controllers\ModelController;
use NewApi\Controllers\ChatController;
use NewApi\Controllers\OAuthController;
use NewApi\Controllers\PasswordController;
use NewApi\Controllers\BillingController;
use NewApi\Controllers\PaymentController;
use NewApi\Controllers\SmsController;
use NewApi\Controllers\LoginLogController;
use NewApi\Controllers\AdminLogController;
use NewApi\Controllers\MailController;
use NewApi\Controllers\NodeController;
use NewApi\Controllers\InvitationController;
use NewApi\Controllers\MetricsController;
use NewApi\Controllers\RoleController;
use NewApi\Controllers\GroupController2;

// Helper functions to wrap handlers with auth middleware
function withAuth(callable $handler): callable {
    return function(Request $request) use ($handler) {
        $auth = NewApi\Middleware\Auth::userAuth();
        return $auth($request, $handler);
    };
}
function withAdminAuth(callable $handler): callable {
    return function(Request $request) use ($handler) {
        $auth = NewApi\Middleware\Auth::adminAuth();
        return $auth($request, $handler);
    };
}

// Initialize router
$router = new Router();

// Global middleware
$router->use(CORS::handle());
$router->use(Logger::handle());
$rateLimiter = new RateLimit(60, 60);
$router->use($rateLimiter->handle());

// Initialize database (lazy)

// ==========================================
// Relay API (OpenAI-compatible endpoints)
// ==========================================
$relayController = new RelayController();

// Models listing
$router->get('/v1/models', fn($req) => $relayController->listModels($req));
$router->get('/v1/models/{model}', fn($req) => $relayController->retrieveModel($req, $req->param('model')));
$router->get('/v1beta/models', fn($req) => $relayController->listModels($req));

// Chat completions
$router->post('/v1/chat/completions', fn($req) => $relayController->relay($req, 'chat_completions'));
$router->post('/v1/completions', fn($req) => $relayController->relay($req, 'completions'));
$router->post('/v1/embeddings', fn($req) => $relayController->relay($req, 'embeddings'));
$router->post('/v1/images/generations', fn($req) => $relayController->relay($req, 'images_generations'));
$router->post('/v1/images/edits', fn($req) => $relayController->relay($req, 'images_edits'));
$router->post('/v1/responses', fn($req) => $relayController->relay($req, 'responses'));

// Claude compatible
$router->post('/v1/messages', fn($req) => $relayController->relay($req, 'messages'));

// ==========================================
// Management API
// ==========================================
$authController = new AuthController();
$systemController = new SystemController();
$channelController = new ChannelController();
$tokenController = new TokenController();
$userController = new UserController();
$logController = new LogController();
$optionController = new OptionController();
$redemptionController = new RedemptionController();
$topupController = new TopupController();
$groupController = new GroupController();
$modelController = new ModelController();
$chatController = new ChatController();
$oAuthController = new OAuthController();
$passwordController = new PasswordController();
$billingController = new BillingController();
$paymentController = new PaymentController();
$smsController = new SmsController();
$loginLogController = new LoginLogController();
$adminLogController = new AdminLogController();
$mailController = new MailController();
$nodeController = new NodeController();
$invitationController = new InvitationController();
$metricsController = new MetricsController();
$roleController = new RoleController();
$groupController2 = new GroupController2();

// Public system routes
$router->get('/api/status', fn($req) => $systemController->getStatus($req));
$router->get('/api/notice', fn($req) => $systemController->getNotice($req));
$router->get('/api/setup', fn($req) => $systemController->getSetup($req));
$router->post('/api/setup', fn($req) => $systemController->postSetup($req));
$router->get('/api/user-agreement', fn($req) => $systemController->getUserAgreement($req));
$router->get('/api/privacy-policy', fn($req) => $systemController->getPrivacyPolicy($req));
$router->get('/api/ratio_config', fn($req) => $systemController->getRatioConfig($req));
$router->get('/api/models', fn($req) => $systemController->searchModels($req));

// Auth routes (public)
$router->post('/api/user/login', fn($req) => $authController->login($req));
$router->post('/api/user/register', fn($req) => $authController->register($req));
$router->post('/api/user/logout', fn($req) => $authController->logout($req));

// User routes (authenticated)
$router->get('/api/user/self', withAuth(fn($req) => $authController->getSelf($req)));
$router->put('/api/user/self', withAuth(fn($req) => $authController->updateSelf($req)));
$router->post('/api/user/generate_access_token', withAuth(fn($req) => $authController->generateAccessToken($req)));

// Admin: Users
$router->get('/api/users', withAdminAuth(fn($req) => $userController->list($req)));
$router->get('/api/user/{id}', withAdminAuth(fn($req) => $userController->get($req)));
$router->post('/api/user', withAdminAuth(fn($req) => $userController->create($req)));
$router->put('/api/user/{id}', withAdminAuth(fn($req) => $userController->update($req)));
$router->delete('/api/user/{id}', withAdminAuth(fn($req) => $userController->delete($req)));
$router->post('/api/user/manage', withAdminAuth(fn($req) => $userController->manage($req)));

// Admin: Channels
$router->get('/api/channels', withAdminAuth(fn($req) => $channelController->list($req)));
$router->get('/api/channel/{id}', withAdminAuth(fn($req) => $channelController->get($req)));
$router->post('/api/channel', withAdminAuth(fn($req) => $channelController->create($req)));
$router->put('/api/channel/{id}', withAdminAuth(fn($req) => $channelController->update($req)));
$router->delete('/api/channel/{id}', withAdminAuth(fn($req) => $channelController->delete($req)));
$router->post('/api/channel/batch', withAdminAuth(fn($req) => $channelController->batchDelete($req)));
$router->post('/api/channel/batch/status', withAdminAuth(fn($req) => $channelController->batchUpdateStatus($req)));
$router->get('/api/channel/test/{id}', withAdminAuth(fn($req) => $channelController->test($req)));
$router->get('/api/channel/test', withAdminAuth(fn($req) => $channelController->testAll($req)));

// Admin: Tokens
$router->get('/api/tokens', withAdminAuth(fn($req) => $tokenController->list($req)));
$router->post('/api/token', withAdminAuth(fn($req) => $tokenController->create($req)));
$router->post('/api/token/batch', withAdminAuth(fn($req) => $tokenController->batchDelete($req)));
$router->post('/api/token/batch/status', withAdminAuth(fn($req) => $tokenController->batchUpdateStatus($req)));
$router->get('/api/token/self', withAuth(fn($req) => $tokenController->list($req)));
$router->post('/api/token/self', withAuth(fn($req) => $tokenController->create($req)));
$router->get('/api/token/{id}', withAdminAuth(fn($req) => $tokenController->get($req)));
$router->put('/api/token/{id}', withAdminAuth(fn($req) => $tokenController->update($req)));
$router->delete('/api/token/{id}', withAdminAuth(fn($req) => $tokenController->delete($req)));
$router->delete('/api/token/self/{id}', withAuth(fn($req) => $tokenController->delete($req)));

// Admin: Logs
$router->get('/api/logs', withAdminAuth(fn($req) => $logController->list($req)));
$router->post('/api/logs/clear', withAdminAuth(fn($req) => $logController->clear($req)));
$router->get('/api/logs/stats', withAdminAuth(fn($req) => $logController->stats($req)));
$router->get('/api/log/self', withAuth(fn($req) => $logController->list($req)));
$router->get('/api/login-logs', withAdminAuth(fn($req) => $loginLogController->list($req)));
$router->get('/api/log/{id}', withAdminAuth(fn($req) => $logController->get($req)));
$router->delete('/api/log/{id}', withAdminAuth(fn($req) => $logController->delete($req)));

// Admin operation logs
$router->get('/api/admin-logs', withAdminAuth(fn($req) => $adminLogController->list($req)));

// Mail/SMTP config (admin only)
$router->get('/api/mail/smtp-config', withAdminAuth(fn($req) => $mailController->getSmtpConfig($req)));
$router->post('/api/mail/smtp-config', withAdminAuth(fn($req) => $mailController->saveSmtpConfig($req)));
$router->post('/api/mail/test-smtp', withAdminAuth(fn($req) => $mailController->testSmtp($req)));
$router->post('/api/mail/test-send', withAdminAuth(fn($req) => $mailController->testSendMail($req)));

// Mail templates (admin only)
$router->get('/api/mail/templates', withAdminAuth(fn($req) => $mailController->listTemplates($req)));
$router->get('/api/mail/templates/all', withAdminAuth(fn($req) => $mailController->getAllTemplates($req)));
$router->get('/api/mail/templates/{id}', withAdminAuth(fn($req) => $mailController->getTemplate($req)));
$router->post('/api/mail/templates', withAdminAuth(fn($req) => $mailController->createTemplate($req)));
$router->put('/api/mail/templates/{id}', withAdminAuth(fn($req) => $mailController->updateTemplate($req)));
$router->delete('/api/mail/templates/{id}', withAdminAuth(fn($req) => $mailController->deleteTemplate($req)));
$router->post('/api/mail/templates/{id}/test', withAdminAuth(fn($req) => $mailController->testTemplate($req)));
$router->post('/api/mail/templates/{id}/preview', withAdminAuth(fn($req) => $mailController->previewTemplate($req)));

// User batch actions
$router->post('/api/users/batch', withAdminAuth(fn($req) => $userController->batchAction($req)));

// Roles
$router->get('/api/roles', withAdminAuth(fn($req) => $roleController->list($req)));
$router->post('/api/roles', withAdminAuth(fn($req) => $roleController->create($req)));
$router->put('/api/roles/{id}', withAdminAuth(fn($req) => $roleController->update($req)));
$router->delete('/api/roles/{id}', withAdminAuth(fn($req) => $roleController->delete($req)));

// User groups
$router->get('/api/groups', withAdminAuth(fn($req) => $groupController2->list($req)));
$router->post('/api/groups', withAdminAuth(fn($req) => $groupController2->create($req)));
$router->put('/api/groups/{id}', withAdminAuth(fn($req) => $groupController2->update($req)));
$router->delete('/api/groups/{id}', withAdminAuth(fn($req) => $groupController2->delete($req)));
$router->post('/api/groups/members/add', withAdminAuth(fn($req) => $groupController2->addMember($req)));
$router->post('/api/groups/members/remove', withAdminAuth(fn($req) => $groupController2->removeMember($req)));

// Invitations
$router->get('/api/invite/my', withAuth(fn($req) => $invitationController->getMyInvite($req)));
$router->post('/api/invite/redeem', fn($req) => $invitationController->redeemInvite($req));
$router->get('/api/invite/stats', withAdminAuth(fn($req) => $invitationController->getStats($req)));
$router->post('/api/invite/reward', withAdminAuth(fn($req) => $invitationController->setReward($req)));

// Nodes
$router->get('/api/nodes', withAdminAuth(fn($req) => $nodeController->list($req)));
$router->post('/api/nodes', withAdminAuth(fn($req) => $nodeController->create($req)));
$router->put('/api/nodes/{id}', withAdminAuth(fn($req) => $nodeController->update($req)));
$router->delete('/api/nodes/{id}', withAdminAuth(fn($req) => $nodeController->delete($req)));
$router->post('/api/nodes/{id}/sync', withAdminAuth(fn($req) => $nodeController->sync($req)));
$router->post('/api/nodes/sync-all', withAdminAuth(fn($req) => $nodeController->syncAll($req)));

// Metrics
$router->get('/api/metrics/realtime', withAdminAuth(fn($req) => $metricsController->getRealtime($req)));
$router->get('/api/metrics/trend', withAdminAuth(fn($req) => $metricsController->getTrend($req)));

// Start the server
// Public Auth API
// SMS (public for sending/verifying codes)
$router->post('/api/auth/public-login', fn($req) => $authController->publicLogin($req));

// SMS (public for sending/verifying codes)
$router->get('/api/sms/status', fn($req) => Response::success(['enabled' => \NewApi\Models\Option::getBool('SmsEnabled', false)]));
$router->post('/api/sms/send-code', fn($req) => $smsController->sendCode($req));
$router->post('/api/sms/verify-code', fn($req) => $smsController->verifyCode($req));

// SMS config (admin only)
$router->get('/api/sms/config', withAdminAuth(fn($req) => $smsController->getConfig($req)));
$router->post('/api/sms/config', withAdminAuth(fn($req) => $smsController->saveConfig($req)));
$router->post('/api/sms/test-send', withAdminAuth(fn($req) => $smsController->testSend($req)));

// IP location (public)
$router->post('/api/ip-location', fn($req) => $systemController->getIpLocation($req));


// SMS config (admin only)
$router->run();
