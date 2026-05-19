<?php

namespace NewApi\Middleware;

use NewApi\Core\Request;
use NewApi\Core\Response;

class RateLimit
{
    private static array $requests = [];
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(): callable
    {
        return function (Request $request, callable $next): ?Response {
            $ip = $request->ip();
            $now = time();
            $windowStart = $now - $this->windowSeconds;

            if (!isset(self::$requests[$ip])) {
                self::$requests[$ip] = [];
            }
            self::$requests[$ip] = array_filter(
                self::$requests[$ip],
                fn($time) => $time > $windowStart
            );

            if (count(self::$requests[$ip]) >= $this->maxRequests) {
                return Response::error('Rate limit exceeded. Try again later.', 429);
            }

            self::$requests[$ip][] = $now;

            $response = $next($request);
            if ($response) {
                $response->withHeader('X-RateLimit-Limit', (string) $this->maxRequests);
                $response->withHeader('X-RateLimit-Remaining', (string) ($this->maxRequests - count(self::$requests[$ip])));
            }
            return $response;
        };
    }
}
