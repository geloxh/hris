# SelfService module (not yet built)

This module is intentionally thin - it's mostly a curated set of read/write endpoints
that call into other modules' Services scoped to $request->user['employee_id'], e.g.
"my payslips", "my leave balance", "my profile", "update my emergency contact".
Most of it already exists: LeaveController::myRequests/balances and
AttendanceController::clockIn/clockOut/index are self-service by design (see routes.php).
Add a thin SelfServiceController only if you want a single /api/me/* namespace instead
of scattering self-service reads across each module's own routes.
