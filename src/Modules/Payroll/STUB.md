# Payroll module (not yet built)

Follow the Employee/TimeAttendance pattern:
- `PayrollModel.php` - table `payroll_runs` (id, period_start, period_end, status, created_by, created_at)
- `PayslipModel.php` - table `payslips` (id, payroll_run_id, employee_id, basic_pay, overtime_pay,
  allowances, deductions_json, tax, net_pay, generated_at)
- `PayrollService.php` - orchestrates: pull attendance + leave data for the period,
  compute gross pay, apply tax/contribution rules, write payslips inside a DB transaction.
- `PayrollController.php` - `POST /api/payroll/runs` (create+process a run, admin/hr only),
  `GET /api/payroll/runs`, `GET /api/payslips?employee_id=` (self-service can only see their own -
  enforce with `$request->user['employee_id']`, same pattern as AttendanceController).

Add to database/migrations/: 011_create_payroll_runs_table.sql, 012_create_payslips_table.sql.
Register routes in src/routes.php under a new "Payroll" section.
