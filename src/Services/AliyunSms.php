<?php

namespace NewApi\Services;

/**
 * 阿里云短信服务
 * 文档：https://help.aliyun.com/document_detail/112148.html
 */
class AliyunSms
{
    private string $accessKeyId;
    private string $accessKeySecret;
    private string $signName;
    private string $endpoint;

    public function __construct()
    {
        $this->accessKeyId = $_ENV['SMS_ALIYUN_ACCESS_KEY_ID'] ?? '';
        $this->accessKeySecret = $_ENV['SMS_ALIYUN_ACCESS_KEY_SECRET'] ?? '';
        $this->signName = $_ENV['SMS_ALIYUN_SIGN_NAME'] ?? '';
        $this->endpoint = $_ENV['SMS_ALIYUN_ENDPOINT'] ?? 'dysmsapi.aliyuncs.com';
    }

    /**
     * 发送验证码
     * @param string $phone 手机号
     * @param string $code 验证码（4-6位）
     * @param string $templateCode 短信模板CODE
     * @return array ['success' => bool, 'message' => string, 'bizId' => string]
     */
    public function sendCode(string $phone, string $code, string $templateCode = 'SMS_123456789'): array
    {
        if (empty($this->accessKeyId) || empty($this->accessKeySecret)) {
            return ['success' => false, 'message' => '阿里云短信配置未设置'];
        }

        $params = [
            'PhoneNumbers' => $phone,
            'SignName' => $this->signName,
            'TemplateCode' => $templateCode,
            'TemplateParam' => json_encode(['code' => $code]),
        ];

        return $this->request('SendSms', $params);
    }

    /**
     * 发送通知短信
     * @param string $phone 手机号
     * @param string $templateCode 短信模板CODE
     * @param array $templateParam 模板参数
     * @return array
     */
    public function sendNotice(string $phone, string $templateCode, array $templateParam = []): array
    {
        if (empty($this->accessKeyId) || empty($this->accessKeySecret)) {
            return ['success' => false, 'message' => '阿里云短信配置未设置'];
        }

        $params = [
            'PhoneNumbers' => $phone,
            'SignName' => $this->signName,
            'TemplateCode' => $templateCode,
            'TemplateParam' => json_encode($templateParam),
        ];

        return $this->request('SendSms', $params);
    }

    /**
     * 查询短信发送状态
     * @param string $phone 手机号
     * @param string $bizId 发送回执ID
     * @param string $sendDate 发送日期 yyyyMMdd
     * @return array
     */
    public function querySendDetails(string $phone, string $bizId, string $sendDate): array
    {
        $params = [
            'PhoneNumber' => $phone,
            'BizId' => $bizId,
            'SendDate' => $sendDate,
            'PageSize' => 10,
            'CurrentPage' => 1,
        ];

        return $this->request('QuerySendDetails', $params);
    }

    /**
     * 发送请求
     */
    private function request(string $action, array $params): array
    {
        $commonParams = [
            'Format' => 'JSON',
            'Version' => '2017-05-25',
            'AccessKeyId' => $this->accessKeyId,
            'SignatureMethod' => 'HMAC-SHA1',
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'SignatureVersion' => '1.0',
            'SignatureNonce' => uniqid(),
            'Action' => $action,
        ];

        $allParams = array_merge($commonParams, $params);
        ksort($allParams);

        $stringToSign = "GET&" . rawurlencode('/') . '&' . rawurlencode($this->buildQueryString($allParams));
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret . '&', true));

        $url = 'https://' . $this->endpoint . '/?' . $this->buildQueryString($allParams) . '&Signature=' . rawurlencode($signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'HTTP Error: ' . $httpCode];
        }

        $data = json_decode($response, true);

        if (isset($data['Code']) && $data['Code'] === 'OK') {
            return [
                'success' => true,
                'message' => '发送成功',
                'bizId' => $data['BizId'] ?? '',
            ];
        }

        return [
            'success' => false,
            'message' => $data['Message'] ?? '发送失败',
            'code' => $data['Code'] ?? 'UNKNOWN',
        ];
    }

    /**
     * 构建查询字符串
     */
    private function buildQueryString(array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        return implode('&', $parts);
    }
}
