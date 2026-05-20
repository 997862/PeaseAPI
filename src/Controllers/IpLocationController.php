<?php
namespace NewApi\Controllers;
use NewApi\Core\Request;
use NewApi\Core\Response;

class IpLocationController
{
    // 简单 IP 归属地查询（使用服务端调用免费 API，避免前端 CORS 问题）
    public function lookup(Request $request): Response
    {
        $ips = $request->input('ips', []);
        if (!is_array($ips)) {
            $ips = [$ips];
        }
        $ips = array_unique(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP)));
        
        $results = [];
        foreach ($ips as $ip) {
            $results[$ip] = $this->getLocation($ip);
        }
        
        return Response::success($results);
    }
    
    private function getLocation(string $ip): string
    {
        // 内网 IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '内网';
        }
        
        // 使用 ip-api.com 免费 API（服务端调用无 CORS 限制）
        $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city&lang=zh-CN";
        
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'header' => 'User-Agent: PeaseAPI/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return '';
        }
        
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            $parts = array_filter([$data['country'] ?? '', $data['regionName'] ?? '', $data['city'] ?? '']);
            return implode(' ', $parts) ?: '';
        }
        
        return '';
    }
}
