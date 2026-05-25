<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
     = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    ->load();
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
function withAuth(callable ): callable {
    return function(Request ) use () {
         = NewApi\Middleware\Auth::userAuth();
        return (, );
    };
}
function withAdminAuth(callable ): callable {
    return function(Request ) use () {
         = NewApi\Middleware\Auth::adminAuth();
        return (, );
    };
}

// Initialize router
 = new Router();

// Global middleware
->use(CORS::handle());
->use(Logger::handle());
 = new RateLimit(60, 60);
->use(->handle());

// Initialize database (lazy)

// ==========================================
// Relay API (OpenAI-compatible endpoints)
// ==========================================
 = new RelayController();

// Models listing
->get('/v1/models', fn() => ->listModels());
->get('/v1/models/{model}', fn() => ->retrieveModel(, ->param('model')));
->get('/v1beta/models', fn() => ->listModels());

// Chat completions
->post('/v1/chat/completions', fn() => ->relay(, 'chat_completions'));
->post('/v1/completions', fn() => ->relay(, 'completions'));
->post('/v1/embeddings', fn() => ->relay(, 'embeddings'));
->post('/v1/images/generations', fn() => ->relay(, 'images_generations'));
->post('/v1/images/edits', fn() => ->relay(, 'images_edits'));
->post('/v1/responses', fn() => ->relay(, 'responses'));

// Claude compatible
->post('/v1/messages', fn() => ->relay(, 'messages'));

// ==========================================
// Management API
// ==========================================
 = new AuthController();
 = new SystemController();
 = new ChannelController();
 = new TokenController();
 = new UserController();
 = new LogController();
 = new OptionController();
 = new RedemptionController();
 = new TopupController();
 = new GroupController();
 = new ModelController();
 = new ChatController();
 = new OAuthController();
 = new PasswordController();
 = new BillingController();
 = new PaymentController();
 = new SmsController();
 = new LoginLogController();
 = new AdminLogController();
 = new MailController();
 = new NodeController();
 = new InvitationController();
 = new MetricsController();
 = new RoleController();
 = new GroupController2();

// Public system routes
->get('/api/status', fn() => ->getStatus());
->get('/api/notice', fn() => ->getNotice());
->get('/api/setup', fn() => ->getSetup());
->post('/api/setup', fn() => ->postSetup());
->get('/api/user-agreement', fn() => ->getUserAgreement());
->get('/api/privacy-policy', fn() => ->getPrivacyPolicy());
->get('/api/ratio_config', fn() => ->getRatioConfig());
->get('/api/models', fn() => ->searchModels());

// Auth routes (public)
->post('/api/user/login', fn() => ->login());
->post('/api/user/register', fn() => ->register());
->post('/api/user/logout', fn() => ->logout());

// User routes (authenticated)
->get('/api/user/self', withAuth(fn() => ->getSelf()));
->put('/api/user/self', withAuth(fn() => ->updateSelf()));
->post('/api/user/generate_access_token', withAuth(fn() => ->generateAccessToken()));

// Admin: Users
->get('/api/users', withAdminAuth(fn() => ->list()));
->get('/api/user/{id}', withAdminAuth(fn() => ->get()));
->post('/api/user', withAdminAuth(fn() => ->create()));
->put('/api/user/{id}', withAdminAuth(fn() => ->update()));
->delete('/api/user/{id}', withAdminAuth(fn() => ->delete()));
->post('/api/user/manage', withAdminAuth(fn() => ->manage()));

// Admin: Channels
->get('/api/channels', withAdminAuth(fn() => ->list()));
->get('/api/channel/{id}', withAdminAuth(fn() => ->get()));
->post('/api/channel', withAdminAuth(fn() => ->create()));
->put('/api/channel/{id}', withAdminAuth(fn() => ->update()));
->delete('/api/channel/{id}', withAdminAuth(fn() => ->delete()));
->post('/api/channel/batch', withAdminAuth(fn() => ->batchDelete()));
->post('/api/channel/batch/status', withAdminAuth(fn() => ->batchUpdateStatus()));
->get('/api/channel/test/{id}', withAdminAuth(fn() => ->test()));
->get('/api/channel/test', withAdminAuth(fn() => ->testAll()));

// Admin: Tokens
->get('/api/tokens', withAdminAuth(fn() => ->list()));
->post('/api/token', withAdminAuth(fn() => ->create()));
->post('/api/token/batch', withAdminAuth(fn() => ->batchDelete()));
->post('/api/token/batch/status', withAdminAuth(fn() => ->batchUpdateStatus()));
->get('/api/token/self', withAuth(fn() => ->list()));
->post('/api/token/self', withAuth(fn() => ->create()));
->get('/api/token/{id}', withAdminAuth(fn() => ->get()));
->put('/api/token/{id}', withAdminAuth(fn() => ->update()));
->delete('/api/token/{id}', withAdminAuth(fn() => ->delete()));
->delete('/api/token/self/{id}', withAuth(fn() => ->delete()));

// Admin: Logs
->get('/api/logs', withAdminAuth(fn() => ->list()));
->post('/api/logs/clear', withAdminAuth(fn() => ->clear()));
->get('/api/logs/stats', withAdminAuth(fn() => ->stats()));
->get('/api/log/self', withAuth(fn() => ->list()));
->get('/api/login-logs', withAdminAuth(fn() => ->list()));
->get('/api/login-logs/self', withAuth(fn() => ->listSelf()));
->get('/api/log/{id}', withAdminAuth(fn() => ->get()));
->delete('/api/log/{id}', withAdminAuth(fn() => ->delete()));

// Admin operation logs
->get('/api/admin-logs', withAdminAuth(fn() => ->list()));

// Options (admin only)
->get('/api/options', withAdminAuth(fn() => ->list()));
->post('/api/options/batch', withAdminAuth(fn() => ->batchUpdate()));
->get('/api/options/{key}', withAdminAuth(fn() => ->get()));

// Mail/SMTP config (admin only)
->get('/api/mail/smtp-config', withAdminAuth(fn() => ->getSmtpConfig()));
->post('/api/mail/smtp-config', withAdminAuth(fn() => ->saveSmtpConfig()));
->post('/api/mail/test-smtp', withAdminAuth(fn() => ->testSmtp()));
->post('/api/mail/test-send', withAdminAuth(fn() => ->testSendMail()));

// Mail templates (admin only)
->get('/api/mail/templates', withAdminAuth(fn() => ->listTemplates()));
->get('/api/mail/templates/all', withAdminAuth(fn() => ->getAllTemplates()));
->get('/api/mail/templates/{id}', withAdminAuth(fn() => ->getTemplate()));
->post('/api/mail/templates', withAdminAuth(fn() => ->createTemplate()));
->put('/api/mail/templates/{id}', withAdminAuth(fn() => ->updateTemplate()));
->delete('/api/mail/templates/{id}', withAdminAuth(fn() => ->deleteTemplate()));
->post('/api/mail/templates/{id}/test', withAdminAuth(fn() => ->testTemplate()));
->post('/api/mail/templates/{id}/preview', withAdminAuth(fn() => ->previewTemplate()));

// User batch actions
->post('/api/users/batch', withAdminAuth(fn() => ->batchAction()));

// Roles
->get('/api/roles', withAdminAuth(fn() => ->list()));
->post('/api/roles', withAdminAuth(fn() => ->create()));
->put('/api/roles/{id}', withAdminAuth(fn() => ->update()));
->delete('/api/roles/{id}', withAdminAuth(fn() => ->delete()));

// User groups
->get('/api/groups', withAdminAuth(fn() => ->list()));
->post('/api/groups', withAdminAuth(fn() => ->create()));
->put('/api/groups/{id}', withAdminAuth(fn() => ->update()));
->delete('/api/groups/{id}', withAdminAuth(fn() => ->delete()));
->post('/api/groups/members/add', withAdminAuth(fn() => ->addMember()));
->post('/api/groups/members/remove', withAdminAuth(fn() => ->removeMember()));

// Invitations
->get('/api/invite/my', withAuth(fn() => ->getMyInvite()));
->post('/api/invite/redeem', fn() => ->redeemInvite());
->get('/api/invite/stats', withAdminAuth(fn() => ->getStats()));
->post('/api/invite/reward', withAdminAuth(fn() => ->setReward()));

// Nodes
->get('/api/nodes', withAdminAuth(fn() => ->list()));
->post('/api/nodes', withAdminAuth(fn() => ->create()));
->put('/api/nodes/{id}', withAdminAuth(fn() => ->update()));
->delete('/api/nodes/{id}', withAdminAuth(fn() => ->delete()));
->post('/api/nodes/{id}/sync', withAdminAuth(fn() => ->sync()));
->post('/api/nodes/sync-all', withAdminAuth(fn() => ->syncAll()));

// Metrics
->get('/api/metrics/realtime', withAdminAuth(fn() => ->getRealtime()));
->get('/api/metrics/trend', withAdminAuth(fn() => ->getTrend()));

// Start the server
// Public Auth API
// SMS (public for sending/verifying codes)
->post('/api/auth/public-login', fn() => ->publicLogin());

// SMS (public for sending/verifying codes)
->get('/api/sms/status', fn() => Response::success(['enabled' => \NewApi\Models\Option::getBool('SmsEnabled', false)]));
->post('/api/sms/send-code', fn() => ->sendCode());
->post('/api/sms/verify-code', fn() => ->verifyCode());

// SMS config (admin only)
->get('/api/sms/config', withAdminAuth(fn() => ->getConfig()));
->post('/api/sms/config', withAdminAuth(fn() => ->saveConfig()));
->post('/api/sms/test-send', withAdminAuth(fn() => ->testSend()));

// IP location (public)
->post('/api/ip-location', fn() => ->getIpLocation());

->run();
