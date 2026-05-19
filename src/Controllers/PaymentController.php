<?php

namespace NewApi\Controllers;

use NewApi\Core\Request;
use NewApi\Core\Response;
use NewApi\Models\Option;
use NewApi\Models\User;
use NewApi\Database\Connection;

class PaymentController
{
    /**
     * 获取支付配置
     */
    public function getConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $keys = [
            'PaymentEnabled',
            'AlipayEnabled',
            'WeChatPayEnabled',
            'AlipayAppID',
            'AlipayPrivateKey',
            'AlipayPublicKey',
            'AlipayNotifyURL',
            'WeChatPayAppID',
            'WeChatPayMchID',
            'WeChatPayAPIKey',
            'WeChatPayNotifyURL',
            'WeChatPayCertPath',
            'MinTopupAmount',
            'TopupRatio',
        ];

        $config = [];
        foreach ($keys as $key) {
            $config[$key] = Option::get($key, '');
        }

        return Response::success($config);
    }

    /**
     * 保存支付配置
     */
    public function saveConfig(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $user = User::find($userId);
        if (!$user || $user->role < ROLE_ADMIN_USER) {
            return Response::error('Admin access required', 403);
        }

        $body = $request->getBody();
        $allowed = [
            'PaymentEnabled', 'AlipayEnabled', 'WeChatPayEnabled',
            'AlipayAppID', 'AlipayPrivateKey', 'AlipayPublicKey', 'AlipayNotifyURL',
            'WeChatPayAppID', 'WeChatPayMchID', 'WeChatPayAPIKey', 'WeChatPayNotifyURL',
            'WeChatPayCertPath', 'MinTopupAmount', 'TopupRatio',
        ];

        foreach ($allowed as $key) {
            if (isset($body[$key])) {
                Option::updateOption($key, $body[$key]);
            }
        }

        return Response::success('保存成功');
    }

    /**
     * 创建充值订单
     */
    public function createOrder(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) return Response::error('Unauthorized', 401);

        $body = $request->getBody();
        $amount = floatval($body['amount'] ?? 0);
        $method = $body['method'] ?? 'alipay'; // alipay or wechat

        $minAmount = floatval(Option::get('MinTopupAmount', 1.00));
        if ($amount < $minAmount) {
            return Response::error('最低充值金额为￥' . $minAmount, 400);
        }

        $topupRatio = floatval(Option::get('TopupRatio', 1.0));
        $quota = intval($amount * 100000 * $topupRatio);

        $db = Connection::getInstance();
        $orderNo = 'PE' . date('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO topups (user_id, quota, amount, order_no, method, status, created_at) VALUES (?, ?, ?, ?, ?, 3, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $quota, $amount, $orderNo, $method, time()]);
        $orderId = $db->lastInsertId();

        // 这里可以对接实际的支付接口
        // 返回订单信息
        return Response::success([
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'amount' => $amount,
            'quota' => $quota,
            'method' => $method,
            'message' => '订单已创建，请完成支付'
        ]);
    }

    /**
     * 支付回调处理
     */
    public function notify(Request $request): Response
    {
        $body = $request->getBody();
        $method = $body['method'] ?? '';

        // 验证签名
        if ($method === 'alipay') {
            // 支付宝签名验证
            $verified = $this->verifyAlipaySign($body);
        } elseif ($method === 'wechat') {
            // 微信支付签名验证
            $verified = $this->verifyWeChatSign($body);
        } else {
            return Response::error('Invalid method', 400);
        }

        if (!$verified) {
            return Response::error('Signature verification failed', 400);
        }

        $orderNo = $body['out_trade_no'] ?? '';
        $tradeNo = $body['trade_no'] ?? '';

        $db = Connection::getInstance();
        $sql = "UPDATE topups SET status = 1, trade_no = ? WHERE order_no = ? AND status = 3";
        $stmt = $db->prepare($sql);
        $stmt->execute([$tradeNo, $orderNo]);

        if ($stmt->rowCount() > 0) {
            // 给用户加配额
            $sql = "SELECT user_id, quota FROM topups WHERE order_no = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderNo]);
            $row = $stmt->fetch();
            if ($row) {
                $sql = "UPDATE users SET quota = quota + ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$row['quota'], $row['user_id']]);
            }
        }

        if ($method === 'alipay') {
            return new Response('success', 200, ['Content-Type' => 'text/plain']);
        }
        return Response::success('OK');
    }

    private function verifyAlipaySign($data): bool
    {
        $publicKey = Option::get('AlipayPublicKey', '');
        if (empty($publicKey)) return false;
        // 实际验证逻辑...
        return true;
    }

    private function verifyWeChatSign($data): bool
    {
        $apiKey = Option::get('WeChatPayAPIKey', '');
        if (empty($apiKey)) return false;
        // 实际验证逻辑...
        return true;
    }
}
