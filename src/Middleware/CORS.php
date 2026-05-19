<?php

namespace NewApi\Middleware;

use NewApi\Core\Request;
use NewApi\Core\Response;

class CORS
{
    public static function handle(): callable
    {
        return function (Request $request, callable $next): ?Response {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Api-Key, anthropic-version, x-goog-api-key');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($request->getMethod() === 'OPTIONS') {
                http_response_code(204);
                return null;
            }

            return $next($request);
        };
    }
}
