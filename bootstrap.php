<?php

declare(strict_types=1);

/**
 * Zero-dependency PSR-4-ish autoloader for the App\ namespace -> src/.
 * No Composer required to run the app; composer.json is still provided
 * in case you want dev-only tooling (PHPUnit, PHP-CS-Fixer, etc).
 */
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/src/Helpers/functions.php';

loadEnv(__DIR__ . '/.env');

date_default_timezone_set((string) config('app.timezone', 'UTC'));

$debug = (bool) config('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) use ($debug) {
    logger($e->getMessage(), 'FATAL', ['trace' => $e->getTraceAsString()]);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'Internal Server Error',
    ]);
    exit;
});
