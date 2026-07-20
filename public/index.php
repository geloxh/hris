<?php

/**
 * Front controller.
 * Nginx (see docker/nginx/default.conf) rewrites every request that isn't a
 * real static file to this script, so this is the single entry point of the API.
 */

declare(strict_types=1);

define('APP_START', microtime(true));

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();

require dirname(__DIR__) . '/src/routes.php'; // registers all routes onto $router

$request = new Request();
$router->dispatch($request);
