<?php

namespace NewApi\Core;

class Router
{
    private array $routes = [];
    private array $groups = [];
    private array $globalMiddleware = [];

    public function use(callable $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function any(string $path, callable $handler): void
    {
        $this->addRoute('*', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $prefix = $this->groups ? implode('', $this->groups) : '';
        $fullPath = $prefix . $path;
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => [],
        ];
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $this->groups[] = $prefix;
        $prevMiddleware = $this->globalMiddleware;
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);

        $callback($this);

        array_pop($this->groups);
        $this->globalMiddleware = $prevMiddleware;

        // Apply group middleware to routes added in this group
        foreach ($this->routes as &$route) {
            if (str_starts_with($route['path'], $prefix) && empty($route['middleware'])) {
                $route['middleware'] = $middleware;
            }
        }
    }

    public function dispatch(Request $request): Response
    {
        $matchedRoute = null;
        $params = [];

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $request->getMethod()) {
                continue;
            }

            if ($this->matchRoute($route['path'], $request->getPath(), $params)) {
                $matchedRoute = $route;
                break;
            }
        }

        if (!$matchedRoute) {
            return Response::error('Not Found', 404);
        }

        $request->setParams($params);

        // Build middleware chain: global middleware first, then route-specific middleware, then the handler
        $middlewareChain = array_merge($this->globalMiddleware, $matchedRoute['middleware']);
        $handler = $matchedRoute['handler'];

        // Build a chain where each middleware calls the next, ending with the actual handler
        $stack = $handler;
        // Reverse so the first middleware in the list wraps the outer layers
        foreach (array_reverse($middlewareChain) as $mw) {
            $next = $stack;
            $stack = function (Request $req) use ($mw, $next): mixed {
                return $mw($req, $next);
            };
        }

        // Run the full chain
        $response = $stack($request);

        if (!$response instanceof Response) {
            $response = Response::json($response);
        }

        return $response;
    }

    private function matchRoute(string $routePath, string $requestPath, array &$params): bool
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return false;
        }

        foreach ($routeParts as $i => $routePart) {
            if (str_starts_with($routePart, '{') && str_ends_with($routePart, '}')) {
                $paramName = substr($routePart, 1, -1);
                $params[$paramName] = $requestParts[$i];
            } elseif ($routePart !== $requestParts[$i]) {
                return false;
            }
        }

        return true;
    }

    public function run(): void
    {
        $request = new Request();

        // Handle CORS preflight
        if ($request->getMethod() === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            http_response_code(204);
            return;
        }

        try {
            $response = $this->dispatch($request);
            $response->send();
        } catch (\Throwable $e) {
            error_log('Router Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $response = Response::error('Internal Server Error: ' . $e->getMessage(), 500);
            $response->send();
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
