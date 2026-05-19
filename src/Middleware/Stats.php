<?php

namespace NewApi\Middleware;

use NewApi\Core\Request;
use NewApi\Core\Response;

class Stats
{
    public static function handle(): callable
    {
        return function (Request $request, callable $next): ?Response {
            $startTime = time_ms();
            $request->setAttribute('start_time', $startTime);

            $response = $next($request);

            return $response;
        };
    }
}
