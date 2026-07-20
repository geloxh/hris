<?php

    namespace App\Middleware;

    use App\Core\Middleware;
    use App\Core\Request;

    class CorsMiddleware implements Middleware {
        
        public function handle(Request $request, callable $next): void {
            $origin = config('cors.allowed_origin', '*');
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Credentials: true');

            if ($request->method === 'OPTIONS') {
                http_response_code(204);
                exit;
            }

            $next($request);
        }
    }
