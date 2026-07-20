<?php

    require __DIR__ . '/../vendor/autoload.php';

    $router = new App\Core\Router();
    $router->addGlobalMiddleware(App\Middleware\CorsMiddleware::class);

    // public routes
    $router->post('/auth/login', [App\Modules\Auth\AuthController::class, 'login']);

    // protected routes
    $router->get( '/employees', [App\Modules\Employee\EmployeeController::class, 'index'], [App\Middleware\AuthMiddleware::class] );

    $router->post( '/employees', [App\Modules\Employee\EmployeeController::class, 'store'], [App\Middleware\AuthMiddleware::class, App\Middleware\RoleMiddleware::only(['admin', 'hr_manager'])] );

    $router->get( '/employees/{id}', [App\Modules\Employee\EmployeeController::class, 'show'], [App\Middleware\AuthMiddleware::class] );

    $router->dispatch( new App\Core\Request() );
