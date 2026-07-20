<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Usage in routes.php (must come after AuthMiddleware so $request->user is set):
 *   $router->post('/employees', [EmployeeController::class, 'store'],
 *       [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
 */
class RoleMiddleware
{
    public static function only(array $roles): \Closure
    {
        return function (Request $request, callable $next) use ($roles) {
            $role = $request->user['role'] ?? null;
            if (!$role || !in_array($role, $roles, true)) {
                Response::error('Forbidden - your role cannot access this resource.', 403);
            }
            $next($request);
        };
    }
}
