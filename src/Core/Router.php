<?php

namespace App\Core;

/**
 * Minimal router. Supports:
 *   $router->get('/employees/{id}', [EmployeeController::class, 'show'], [AuthMiddleware::class]);
 * Route params ({id}) are extracted and set on $request->params.
 */
class Router
{
    private array $routes = [];
    /** @var callable[] middleware run before every route, regardless of group */
    private array $globalMiddleware = [];

    public function addGlobalMiddleware(callable|array|string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $this->compile($path),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }

            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $request->params[$key] = $value;
                }
            }

            try {
                $this->runMiddlewareChain(
                    array_merge($this->globalMiddleware, $route['middleware']),
                    $request,
                    function (Request $request) use ($route) {
                        $this->invokeHandler($route['handler'], $request);
                    }
                );
            } catch (\Throwable $e) {
                $this->handleException($e);
            }
            return;
        }

        Response::error('Not Found', 404);
    }

    private function runMiddlewareChain(array $middleware, Request $request, callable $final): void
    {
        $next = $final;
        foreach (array_reverse($middleware) as $mw) {
            $current = $next;
            $next = function (Request $request) use ($mw, $current) {
                if ($mw instanceof \Closure) {
                    // e.g. RoleMiddleware::only(['admin', 'hr']) returns a closure
                    $mw($request, $current);
                    return;
                }
                $instance = is_string($mw) ? new $mw() : $mw;
                $instance->handle($request, $current);
            };
        }
        $next($request);
    }

    private function invokeHandler(callable|array $handler, Request $request): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method($request);
            return;
        }
        $handler($request);
    }

    private function handleException(\Throwable $e): void
    {
        logger($e->getMessage(), 'ERROR', ['trace' => $e->getTraceAsString()]);

        if ($e instanceof \App\Core\ValidationException) {
            Response::error($e->getMessage(), 422, $e->errors);
        }

        $debug = config('app.debug');
        Response::error(
            $debug ? $e->getMessage() : 'Internal Server Error',
            500,
            $debug ? ['trace' => explode("\n", $e->getTraceAsString())] : []
        );
    }
}
