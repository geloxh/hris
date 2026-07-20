<?php

namespace App\Core;

interface Middleware
{
    /**
     * @param Request $request
     * @param callable $next call $next($request) to continue the chain
     */
    public function handle(Request $request, callable $next): void;
}
