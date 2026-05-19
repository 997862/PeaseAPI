<?php

namespace NewApi\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $headers = [];
    private array $query = [];
    private array $body = [];
    private array $params = [];
    private array $attributes = [];
    private ?string $rawBody = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $this->query = $_GET;

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $this->headers[strtolower($headerName)] = $value;
            }
        }

        $contentType = $this->getContentType();
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $this->rawBody = file_get_contents('php://input');
            if ($contentType === 'application/json') {
                $decoded = json_decode($this->rawBody, true);
                $this->body = is_array($decoded) ? $decoded : [];
            } else {
                $this->body = $_POST;
            }
        }
    }

    public function getMethod(): string { return $this->method; }
    public function getUri(): string { return $this->uri; }
    public function getPath(): string { return $this->path; }
    public function getHeaders(): array { return $this->headers; }
    public function getQuery(): array { return $this->query; }
    public function getBody(): array { return $this->body; }
    public function getParams(): array { return $this->params; }
    public function getRawBody(): ?string { return $this->rawBody; }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function getContentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return strpos($this->getContentType(), 'application/json') !== false;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function ip(): string
    {
        return \NewApi\Utils\get_client_ip();
    }
}
