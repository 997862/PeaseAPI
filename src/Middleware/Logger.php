<?php

namespace NewApi\Middleware;

use NewApi\Core\Request;
use NewApi\Core\Response;

class Logger
{
    public static function handle(): callable
    {
        return function (Request $request, callable $next): ?Response {
            $startTime = microtime(true);
            $requestId = \NewApi\Utils\get_request_id();
            $request->setAttribute('request_id', $requestId);
            $request->setAttribute('start_time', $startTime);

            $response = $next($request);

            $duration = (microtime(true) - $startTime) * 1000;
            $method = $request->getMethod();
            $path = $request->getPath();
            $status = $response ? $response->getStatusCode() : 200;
            $ip = $request->ip();

            \NewApi\Utils\log_info(sprintf(
                '%s %s %s %d %.1fms',
                $requestId, $method, $path, $status, $duration
            ));

            return $response;
        };
    }
}
