<?php

namespace NewApi\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [
        'Content-Type' => 'application/json; charset=utf-8',
    ];
    private mixed $body = null;

    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self();
        $response->statusCode = $status;
        $response->body = $data;
        return $response;
    }

    public static function success(mixed $data = null, string $message = 'success'): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function error(string $message, int $status = 400, int $code = 0): self
    {
        return self::json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $status);
    }

    public static function openaiError(string $message, string $type = 'invalid_request_error', int $status = 400, ?string $param = null, ?string $code = null): self
    {
        return self::json([
            'error' => [
                'message' => $message,
                'type' => $type,
                'param' => $param,
                'code' => $code,
            ],
        ], $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function withStatus(int $status): self
    {
        $this->statusCode = $status;
        return $this;
    }

    public function stream(callable $generator): void
    {
        $this->sendHeaders();
        foreach ($generator() as $chunk) {
            echo $chunk;
            if (ob_get_level() > 0) ob_flush();
            flush();
        }
    }

    public function send(): void
    {
        $this->sendHeaders();
        if ($this->body !== null) {
            if (is_string($this->body)) {
                echo $this->body;
            } else {
                $encoded = json_encode($this->body, JSON_UNESCAPED_UNICODE);
                if ($encoded === false) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'JSON encoding error: ' . json_last_error_msg(),
                        'body_type' => gettype($this->body),
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo $encoded;
                }
            }
        }
    }

    private function sendHeaders(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }

    public function getStatusCode(): int { return $this->statusCode; }
    public function getHeaders(): array { return $this->headers; }
    public function getBody(): mixed { return $this->body; }
}
