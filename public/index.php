<?php

    require __DIR__ . '/../vendor/autoload.php';

    $router = new App\Core\Router();
    $router->addGlobalMiddleware(App\Middleware\CorsMiddleware::class);

    $auth = [App\Middleware\AuthMiddleware::class];
    $hr = [App\Middleware\AuthMiddleware::class, App\Middleware\RoleMiddleware::only(['admin', 'hr_manager'])];
    $admin = [App\Middleware\AuthMiddleware::class, App\Middleware\RoleMiddleware::only(['admin'])];

    // ── Auth ── //
    $router->post( '/auth/login', [App\Modules\Auth\AuthController::class, 'login']);
    $router->post( '/auth/logout', [App\Modules\Auth\AuthController::class, 'logout'], $auth);
    $router->get( '/auth/me', [App\Modules\Auth\AuthController::class, 'me'], $auth);
    $router->post( '/auth/change-password', [App\Modules\Auth\AuthController::class, 'changePassword'],  $auth);

    // ── Employees ── //
    $router->get( '/employees', [App\Modules\Employee\EmployeeController::class, 'index'], $auth);
    $router->post( '/employees', [App\Modules\Employee\EmployeeController::class, 'store'], $hr);
    $router->get( '/employees/{id}', [App\Modules\Employee\EmployeeController::class, 'show'], $auth);
    $router->patch( '/employees/{id}', [App\Modules\Employee\EmployeeController::class, 'update'], $hr);
    $router->delete( '/employees/{id}', [App\Modules\Employee\EmployeeController::class, 'destroy'], $hr);
    $router->post( '/employees/{id}/terminate', [App\Modules\Employee\EmployeeController::class, 'terminate'], $hr);

    // ── Departments & Positions ── // 
    $router->get( '/departments', [App\Modules\EmployeeData\DepartmentController::class, 'index'], $auth);
    $router->post( '/departments', [App\Modules\EmployeeData\DepartmentController::class, 'store'], $hr);
    $router->get( '/departments/{id}', [App\Modules\EmployeeData\DepartmentController::class, 'show'], $auth);
    $router->put( '/departments/{id}', [App\Modules\EmployeeData\DepartmentController::class, 'update'], $hr);
    $router->delete('/departments/{id}', [App\Modules\EmployeeData\DepartmentController::class, 'destroy'], $hr);

    $router->get( '/positions', [App\Modules\EmployeeData\PositionController::class, 'index'], $auth);
    $router->post( '/positions', [App\Modules\EmployeeData\PositionController::class, 'store'], $hr);
    $router->put( '/positions/{id}', [App\Modules\EmployeeData\PositionController::class, 'update'], $hr);
    $router->delete('/positions/{id}', [App\Modules\EmployeeData\PositionController::class, 'destroy'], $hr);

    // ── Attendance ── //
    $router->post( '/attendance/clock-in', [App\Modules\TimeAttendance\AttendanceController::class, 'clockIn'], $auth);
    $router->post( '/attendance/clock-out', [App\Modules\TimeAttendance\AttendanceController::class, 'clockOut'], $auth);
    $router->get( '/attendance', [App\Modules\TimeAttendance\AttendanceController::class, 'index'], $auth);
    $router->post( '/attendance/manual', [App\Modules\TimeAttendance\AttendanceController::class, 'storeManual'], $hr);

    // ── Leave ── //
    $router->get( '/leave-types', [App\Modules\TimeAttendance\LeaveController::class, 'types'], $auth);
    $router->get( '/leave/balances', [App\Modules\TimeAttendance\LeaveController::class, 'balances'], $auth);
    $router->get( '/leave/requests', [App\Modules\TimeAttendance\LeaveController::class, 'myRequests'], $auth);
    $router->get( '/leave/requests/pending', [App\Modules\TimeAttendance\LeaveController::class, 'pending'], $hr);
    $router->post( '/leave/requests', [App\Modules\TimeAttendance\LeaveController::class, 'store'], $auth);
    $router->post( '/leave/requests/{id}/approve', [App\Modules\TimeAttendance\LeaveController::class, 'approve'], $hr);
    $router->post( '/leave/requests/{id}/reject', [App\Modules\TimeAttendance\LeaveController::class, 'reject'], $hr);
    $router->post( '/leave/requests/{id}/cancel', [App\Modules\TimeAttendance\LeaveController::class, 'cancel'], $auth);

    // ── Payroll ── //
    $router->get( '/payroll/runs', [App\Modules\Payroll\PayrollController::class, 'index'], $hr);
    $router->post( '/payroll/runs', [App\Modules\Payroll\PayrollController::class, 'store'], $hr);
    $router->get( '/payroll/runs/{id}', [App\Modules\Payroll\PayrollController::class, 'show'], $hr);
    $router->post( '/payroll/runs/{id}/process', [App\Modules\Payroll\PayrollController::class, 'process'], $hr);
    $router->post( '/payroll/runs/{id}/lock', [App\Modules\Payroll\PayrollController::class, 'lock'], $hr);
    $router->get( '/payroll/payslips/{id}', [App\Modules\Payroll\PayrollController::class, 'payslip'], $auth);
    $router->get( '/payroll/my-payslips', [App\Modules\Payroll\PayrollController::class, 'myPayslips'], $auth);
    $router->get( '/payroll/compensations/{employee_id}', [App\Modules\Payroll\PayrollController::class, 'compensation'], $hr );
    $router->post( '/payroll/compensations', [App\Modules\Payroll\PayrollController::class, 'storeCompensation'], $hr );
    $router->get( '/payroll/benefits/{employee_id}', [App\Modules\Payroll\PayrollController::class, 'benefits'], $hr );
    $router->post( '/payroll/benefits', [App\Modules\Payroll\PayrollController::class, 'storeBenefit'], $hr );

    // ── Recruitment ── //
    $router->get( '/recruitment/postings', [App\Modules\Recruitment\RecruitmentController::class, 'postings'], $auth );
    $router->post( '/recruitment/postings', [App\Modules\Recruitment\RecruitmentController::class, 'storePosting'], $hr );
    $router->get( '/recruitment/postings/{id}', [App\Modules\Recruitment\RecruitmentController::class, 'showPosting'], $auth );
    $router->put( '/recruitment/postings/{id}', [App\Modules\Recruitment\RecruitmentController::class, 'updatePosting'], $hr );
    $router->get( '/recruitment/postings/{id}/applicants', [App\Modules\Recruitment\RecruitmentController::class, 'applicants'], $hr );
    $router->post( '/recruitment/postings/{id}/applicants', [App\Modules\Recruitment\RecruitmentController::class, 'storeApplicant'], $hr );
    $router->patch( '/recruitment/applicants/{id}/stage', [App\Modules\Recruitment\RecruitmentController::class, 'updateStage'], $hr );
    $router->get( '/recruitment/onboarding/{employee_id}', [App\Modules\Recruitment\RecruitmentController::class, 'onboardingTasks'],$auth );
    $router->post( '/recruitment/onboarding', [App\Modules\Recruitment\RecruitmentController::class, 'storeTask'], $hr );
    $router->patch( '/recruitment/onboarding/{id}/complete', [App\Modules\Recruitment\RecruitmentController::class, 'completeTask'], $auth );

    // ── Performance ── //
    $router->get( '/performance/cycles', [App\Modules\Performance\PerformanceController::class, 'cycles'], $auth );
    $router->post( '/performance/cycles', [App\Modules\Performance\PerformanceController::class, 'storeCycle'], $hr );
    $router->get(  '/performance/cycles/{id}', [App\Modules\Performance\PerformanceController::class, 'showCycle'], $auth);
    $router->patch( '/performance/cycles/{id}/status', [App\Modules\Performance\PerformanceController::class, 'updateCycleStatus'], $hr );
    $router->get(  '/performance/goals', [App\Modules\Performance\PerformanceController::class, 'goals'], $auth );
    $router->post( '/performance/goals', [App\Modules\Performance\PerformanceController::class, 'storeGoal'], $auth );
    $router->patch( '/performance/goals/{id}', [App\Modules\Performance\PerformanceController::class, 'updateGoal'], $auth );
    $router->get( '/performance/cycles/{id}/evaluations', [App\Modules\Performance\PerformanceController::class, 'evaluations'], $auth );
    $router->post( '/performance/evaluations', [App\Modules\Performance\PerformanceController::class, 'storeEvaluation'], $auth );
    $router->patch( '/performance/evaluations/{id}', [App\Modules\Performance\PerformanceController::class, 'updateEvaluation'], $auth );
    $router->post( '/performance/evaluations/{id}/submit', [App\Modules\Performance\PerformanceController::class, 'submitEvaluation'], $auth );

    // ── Analytics ── //
    $router->get( '/analytics/headcount', [App\Modules\Analytics\AnalyticsController::class, 'headcount'], $hr );
    $router->get( '/analytics/turnover', [App\Modules\Analytics\AnalyticsController::class, 'turnover'], $hr );
    $router->get( '/analytics/attendance', [App\Modules\Analytics\AnalyticsController::class, 'attendance'], $hr );
    $router->get( '/analytics/leave', [App\Modules\Analytics\AnalyticsController::class, 'leave'], $hr );
    $router->get( '/analytics/payroll', [App\Modules\Analytics\AnalyticsController::class, 'payroll'], $hr );
    $router->get( '/analytics/recruitment/{job_posting_id}/funnel', [App\Modules\Analytics\AnalyticsController::class, 'recruitmentFunnel'],$hr );
    $router->get( '/analytics/performance/{cycle_id}', [App\Modules\Analytics\AnalyticsController::class, 'performance'], $hr );
    $router->get( '/analytics/birthdays', [App\Modules\Analytics\AnalyticsController::class, 'birthdays'], $auth );
    $router->get( '/analytics/new-hires', [App\Modules\Analytics\AnalyticsController::class, 'newHires'], $hr );

    // ── Self Service ── //
    $router->get( '/me/profile', [App\Modules\SelfService\SelfServiceController::class, 'profile'], $auth );
    $router->get( '/me/dashboard', [App\Modules\SelfService\SelfServiceController::class, 'dashboard'], $auth );
    $router->get( '/me/attendance', [App\Modules\SelfService\SelfServiceController::class, 'attendance'], $auth );
    $router->get( '/me/leave/balances', [App\Modules\SelfService\SelfServiceController::class, 'leaveBalances'], $auth );
    $router->get( '/me/leave/requests', [App\Modules\SelfService\SelfServiceController::class, 'leaveRequests'], $auth );
    $router->post( '/me/leave/requests', [App\Modules\SelfService\SelfServiceController::class, 'fileLeave'], $auth );
    $router->get( '/me/payslips', [App\Modules\SelfService\SelfServiceController::class, 'payslips'], $auth );
    $router->get( '/me/goals', [App\Modules\SelfService\SelfServiceController::class, 'goals'], $auth );
    $router->get( '/me/evaluations', [App\Modules\SelfService\SelfServiceController::class, 'evaluations'], $auth );
    $router->get( '/me/onboarding', [App\Modules\SelfService\SelfServiceController::class, 'onboarding'], $auth );

    // ── Compliance ── //
    $router->get( '/compliance/policies', [App\Modules\Compliance\ComplianceController::class, 'policies'], $auth );
    $router->get( '/compliance/policies/status', [App\Modules\Compliance\ComplianceController::class, 'myPolicies'], $auth);
    $router->post( '/compliance/policies', [App\Modules\Compliance\ComplianceController::class, 'storePolicy'], $hr );
    $router->put( '/compliance/policies/{id}', [App\Modules\Compliance\ComplianceController::class, 'updatePolicy'], $hr );
    $router->post( '/compliance/policies/{id}/acknowledge', [App\Modules\Compliance\ComplianceController::class, 'acknowledge'], $auth );
    $router->get( '/compliance/policies/{id}/acknowledgments', [App\Modules\Compliance\ComplianceController::class, 'acknowledgments'], $hr );
    $router->get( '/compliance/audit-logs', [App\Modules\Compliance\ComplianceController::class, 'auditLogs'], $admin );
    $router->get( '/compliance/audit-logs/employee/{employee_id}', [App\Modules\Compliance\ComplianceController::class, 'employeeAuditTrail'],$hr );

    // ── Learning ── //
    $router->get( '/learning/courses', [App\Modules\Learning\LearningController::class, 'courses'], $auth );
    $router->post( '/learning/courses', [App\Modules\Learning\LearningController::class, 'storeCourse'], $hr );
    $router->get( '/learning/courses/{id}', [App\Modules\Learning\LearningController::class, 'showCourse'], $auth );
    $router->put( '/learning/courses/{id}', [App\Modules\Learning\LearningController::class, 'updateCourse'], $hr );
    $router->get( '/learning/courses/{id}/enrollments', [App\Modules\Learning\LearningController::class, 'courseEnrollments'], $hr );
    $router->post( '/learning/courses/{id}/enroll', [App\Modules\Learning\LearningController::class, 'enroll'], $auth);
    $router->patch( '/learning/enrollments/{id}/progress', [App\Modules\Learning\LearningController::class, 'updateProgress'], $auth );
    $router->get( '/learning/my-courses', [App\Modules\Learning\LearningController::class, 'myCourses'], $auth);
    $router->get( '/learning/certifications/expiring', [App\Modules\Learning\LearningController::class, 'expiring'], $hr );
    $router->get( '/learning/certifications/{employee_id}', [App\Modules\Learning\LearningController::class, 'certifications'], $auth );
    $router->post( '/learning/certifications', [App\Modules\Learning\LearningController::class, 'storeCertification'], $auth );
    $router->delete( '/learning/certifications/{id}', [App\Modules\Learning\LearningController::class, 'deleteCertification'], $auth );

    $router->dispatch(new App\Core\Request());
