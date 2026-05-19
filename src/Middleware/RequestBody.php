<?php

namespace NewApi\Middleware;

use NewApi\Core\Request;
use NewApi\Core\Response;

class RequestBody
{
    private int $maxSize;

    public function __construct(int $maxSizeMb = 32)
    {
        $this->maxSize = $maxSizeMb * 1024 * 1024;
    }

    public function handle(): callable
    {
        return function (Request $request, callable $next): ?Response {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            if ($contentLength > $this->maxSize) {
                return Response::error('Request body too large', 413);
            }
            return $next($request);
        };
    }
}
