<?php

    /** @var \App\Core\Router $router injected by public/index.php */

    use App\Middleware\AuthMiddleware;
    use App\Middleware\CorsMiddleware;
    use App\Middleware\RoleMiddleware;
    use App\Modules\Auth\AuthController;
    use App\Modules\Employee\EmployeeController;
    use App\Modules\EmployeeData\DepartmentController;
    use App\Modules\EmployeeData\PositionController;
    use App\Modules\TimeAttendance\AttendanceController;
    use App\Modules\TimeAttendance\LeaveController;

    $router->addGlobalMiddleware(CorsMiddleware::class);

    $router->get('/api/health', function () {
        \App\Core\Response::json(['status' => 'ok', 'time' => date(DATE_ATOM)]);
    });

    /* --- Auth --- */
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->post('/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
    $router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
    $router->post('/api/auth/change-password', [AuthController::class, 'changePassword'], [AuthMiddleware::class]);

    /* --- Employee --- */
    $router->get('/api/employees', [EmployeeController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/api/employees/{id}', [EmployeeController::class, 'show'], [AuthMiddleware::class]);
    $router->post('/api/employees', [EmployeeController::class, 'store'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->put('/api/employees/{id}', [EmployeeController::class, 'update'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->patch('/api/employees/{id}', [EmployeeController::class, 'update'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->delete('/api/employees/{id}', [EmployeeController::class, 'destroy'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->post('/api/employees/{id}/terminate', [EmployeeController::class, 'terminate'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);

    /* --- Departments/Positions --- */
    $router->get('/api/departments', [DepartmentController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/api/departments/{id}', [DepartmentController::class, 'show'], [AuthMiddleware::class]);
    $router->post('/api/departments', [DepartmentController::class, 'store'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->put('/api/departments/{id}', [DepartmentController::class, 'update'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->delete('/api/departments/{id}', [DepartmentController::class, 'destroy'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);

    $router->get('/api/positions', [PositionController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/api/positions', [PositionController::class, 'store'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->put('/api/positions/{id}', [PositionController::class, 'update'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);
    $router->delete('/api/positions/{id}', [PositionController::class, 'destroy'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr'])]);

    /* --- Time & Attendance --- */
    $router->post('/api/attendance/clock-in', [AttendanceController::class, 'clockIn'], [AuthMiddleware::class]);
    $router->post('/api/attendance/clock-out', [AttendanceController::class, 'clockOut'], [AuthMiddleware::class]);
    $router->get('/api/attendance', [AttendanceController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/api/attendance/manual', [AttendanceController::class, 'storeManual'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr', 'manager'])]);

    /* --- Leave --- */
    $router->get('/api/leave-types', [LeaveController::class, 'types'], [AuthMiddleware::class]);
    $router->get('/api/leave/balances', [LeaveController::class, 'balances'], [AuthMiddleware::class]);
    $router->get('/api/leave/requests', [LeaveController::class, 'myRequests'], [AuthMiddleware::class]);
    $router->get('/api/leave/requests/pending', [LeaveController::class, 'pending'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr', 'manager'])]);
    $router->post('/api/leave/requests', [LeaveController::class, 'store'], [AuthMiddleware::class]);
    $router->post('/api/leave/requests/{id}/approve', [LeaveController::class, 'approve'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr', 'manager'])]);
    $router->post('/api/leave/requests/{id}/reject', [LeaveController::class, 'reject'], [AuthMiddleware::class, RoleMiddleware::only(['admin', 'hr', 'manager'])]);
    $router->post('/api/leave/requests/{id}/cancel', [LeaveController::class, 'cancel'], [AuthMiddleware::class]);

    /* --- Not-yet-built modules
    Payroll, Recruitment, Performance, Learning, SelfService, Analytics, Compliance
    each get their own routes.php-section here once their controllers exist -
    see src/Modules/<Module>/STUB.md for the suggested next file to write. */
