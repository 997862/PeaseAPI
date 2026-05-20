<?php

namespace NewApi\Services;

/**
 * 腾讯云短信服务
 * 文档：https://cloud.tencent.com/document/product/382/59766
 */
class TencentSms
{
    private string $secretId;
    private string $secretKey;
    private string $sdkAppId;
    private string $signName;
    private string $endpoint = 'sms.tencentcloudapi.com';

    public function __construct(?array $config = null)
    {
        if ($config !== null) {
            $this->secretId = $config['SmsTencentSecretId'] ?? '';
            $this->secretKey = $config['SmsTencentSecretKey'] ?? '';
            $this->sdkAppId = $config['SmsTencentSdkAppId'] ?? '';
            $this->signName = $config['SmsTencentSignName'] ?? '';
        } else {
            $this->secretId = $_ENV['SMS_TENCENT_SECRET_ID'] ?? \NewApi\Models\Option::get('SmsTencentSecretId', '');
            $this->secretKey = $_ENV['SMS_TENCENT_SECRET_KEY'] ?? \NewApi\Models\Option::get('SmsTencentSecretKey', '');
            $this->sdkAppId = $_ENV['SMS_TENCENT_SDK_APP_ID'] ?? \NewApi\Models\Option::get('SmsTencentSdkAppId', '');
            $this->signName = $_ENV['SMS_TENCENT_SIGN_NAME'] ?? \NewApi\Models\Option::get('SmsTencentSignName', '');
        }
    }

    /**
     * 发送验证码
     * @param string $phone 手机号（不带+86）
     * @param string $code 验证码（4-6位）
     * @param string $templateId 短信模板ID
     * @return array ['success' => bool, 'message' => string, 'sendStatus' => array]
     */
    public function sendCode(string $phone, string $code, string $templateId = '123456'): array
    {
        if (empty($this->secretId) || empty($this->secretKey) || empty($this->sdkAppId)) {
            return ['success' => false, 'message' => '腾讯云短信配置未设置'];
        }

        // 移除手机号中的国家码和符号
        $phone = preg_replace('/[^\d]/', '', $phone);

        $params = [
            'PhoneNumberSet' => ['+86' . $phone],
            'SmsSdkAppId' => $this->sdkAppId,
            'SignName' => $this->signName,
            'TemplateId' => $templateId,
            'TemplateParamSet' => [$code],
        ];

        return $this->request('SendSms', $params);
    }

    /**
     * 发送通知短信
     * @param string $phone 手机号
     * @param string $templateId 短信模板ID
     * @param array $templateParams 模板参数数组
     * @return array
     */
    public function sendNotice(string $phone, string $templateId, array $templateParams = []): array
    {
        if (empty($this->secretId) || empty($this->secretKey) || empty($this->sdkAppId)) {
            return ['success' => false, 'message' => '腾讯云短信配置未设置'];
        }

        $phone = preg_replace('/[^\d]/', '', $phone);

        $params = [
            'PhoneNumberSet' => ['+86' . $phone],
            'SmsSdkAppId' => $this->sdkAppId,
            'SignName' => $this->signName,
            'TemplateId' => $templateId,
            'TemplateParamSet' => $templateParams,
        ];

        return $this->request('SendSms', $params);
    }

    /**
     * 发送请求（使用腾讯云 API v3 签名）
     */
    private function request(string $action, array $params): array
    {
        $payload = json_encode($params);
        $timestamp = time();
        $nonce = mt_rand(100000, 999999);

        // 签名步骤
        $canonicalRequest = "POST\n/\n\ncontent-type:application/json\nhost:{$this->endpoint}\n\ncontent-type;host\n" . hash('sha256', $payload);
        $stringToSign = "TC3-HMAC-SHA256\n{$timestamp}\n" . date('Y-m-d', $timestamp) . "/sms/tc3_request\n" . hash('sha256', $canonicalRequest);

        $secretDate = hash_hmac('sha256', date('Y-m-d', $timestamp), 'TC3' . $this->secretKey, true);
        $secretService = hash_hmac('sha256', 'sms', $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);

        $authorization = sprintf(
            'TC3-HMAC-SHA256 Credential=%s/%s/sms/tc3_request, SignedHeaders=content-type;host, Signature=%s',
            $this->secretId,
            date('Y-m-d', $timestamp),
            $signature
        );

        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $authorization,
            'Host: ' . $this->endpoint,
            'X-TC-Action: ' . $action,
            'X-TC-Timestamp: ' . $timestamp,
            'X-TC-Version: 2021-01-11',
            'X-TC-Nonce: ' . $nonce,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->endpoint . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'HTTP Error: ' . $httpCode];
        }

        $data = json_decode($response, true);

        if (isset($data['Response'])) {
            $resp = $data['Response'];
            if (isset($resp['Error'])) {
                return [
                    'success' => false,
                    'message' => $resp['Error']['Message'] ?? '发送失败',
                    'code' => $resp['Error']['Code'] ?? 'UNKNOWN',
                ];
            }

            if (isset($resp['SendStatusSet'])) {
                foreach ($resp['SendStatusSet'] as $status) {
                    if ($status['Code'] !== 'Ok') {
                        return [
                            'success' => false,
                            'message' => $status['Message'] ?? '发送失败',
                            'code' => $status['Code'],
                        ];
                    }
                }
                return [
                    'success' => true,
                    'message' => '发送成功',
                    'serialNo' => $resp['SendStatusSet'][0]['SerialNo'] ?? '',
                    'sendStatus' => $resp['SendStatusSet'],
                ];
            }
        }

        return ['success' => false, 'message' => '发送失败'];
    }
}
