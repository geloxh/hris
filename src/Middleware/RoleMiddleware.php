<?php

    namespace App\Middleware;

    use App\Core\Request;
    use App\Core\Response;

    class RoleMiddleware {
        public static function only(array $roles): \Closure {
            return function (Request $request, callable $next) use ($roles) {
                $role = $request->user['role'] ?? null;
                if (!$role || !in_array($role, $roles, true)) {
                    Response::error('Forbidden - your role cannot access this resource.', 403);
                }
                $next($request);
            };
        }
    }
