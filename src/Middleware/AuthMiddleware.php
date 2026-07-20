<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        $token = $request->bearerToken();
        if (!$token) {
            Response::error('Unauthenticated.', 401);
        }

        $user = (new Auth())->userFromToken($token);
        if (!$user) {
            Response::error('Unauthenticated - invalid or expired token.', 401);
        }

        $request->user = $user;
        $next($request);
    }
}
