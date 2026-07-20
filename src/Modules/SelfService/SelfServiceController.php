<?php

    namespace App\Modules\SelfService;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;
    use App\Modules\Employee\EmployeeModel;
    use App\Modules\Payroll\PayslipModel;
    use App\Modules\TimeAttendance\AttendanceModel;
    use App\Modules\TimeAttendance\LeaveBalanceModel;
    use App\Modules\TimeAttendance\LeaveRequestModel;
    use App\Modules\TimeAttendance\LeaveService;
    use App\Modules\TimeAttendance\LeaveTypeModel;
    use App\Modules\Recruitment\OnboardingTaskModel;
    use App\Modules\Performance\GoalModel;
    use App\Modules\Performance\EvaluationModel;
    use App\Core\Validator;

    class SelfServiceController extends Controller {
        private EmployeeModel $employees;
        private AttendanceModel $attendance;
        private LeaveRequestModel $leaveRequests;
        private LeaveBalanceModel $leaveBalances;
        private LeaveTypeModel $leaveTypes;
        private LeaveService $leaveService;
        private PayslipModel $payslips;
        private OnboardingTaskModel $onboardingTasks;
        private GoalModel $goals;
        private EvaluationModel $evaluations;

        public function __construct() {
            $this->employees = new EmployeeModel();
            $this->attendance = new AttendanceModel();
            $this->leaveRequests = new LeaveRequestModel();
            $this->leaveBalances = new LeaveBalanceModel();
            $this->leaveTypes = new LeaveTypeModel();
            $this->leaveService = new LeaveService();
            $this->payslips = new PayslipModel();
            $this->onboardingTasks = new OnboardingTaskModel();
            $this->goals = new GoalModel();
            $this->evaluations = new EvaluationModel();
        }

        /** GET /api/me/profile */
        public function profile(Request $request): void {
            $employee = $this->employees->withRelations((int) $request->user['employee_id']);
            if (!$employee) Response::error('Profile not found.', 404);
            Response::json($employee);
        }

        /** GET /api/me/dashboard */
        public function dashboard(Request $request): void
        {
            $employeeId = (int) $request->user['employee_id'];
            $today = date('Y-m-d');
            $year = (int) date('Y');

            Response::json([
                'attendance_today' => $this->attendance->findForEmployeeOnDate($employeeId, $today),
                'leave_balances' => $this->leaveBalances->forEmployeeYear($employeeId, $year),
                'pending_leaves' => $this->leaveRequests->forEmployee($employeeId),
                'latest_payslip' => $this->payslips->forEmployee($employeeId)[0] ?? null,
                'onboarding_tasks' => $this->onboardingTasks->forEmployee($employeeId),
                'goals' => $this->goals->forEmployee($employeeId),
            ]);
        }

        /** GET /api/me/attendance?from=&to= */
        public function attendance(Request $request): void {
            $employeeId = (int) $request->user['employee_id'];
            Response::json($this->attendance->forEmployee(
                $employeeId,
                $request->input('from', date('Y-m-01')),
                $request->input('to',   date('Y-m-d'))
            ));
        }

        /** GET /api/me/leave/balances */
        public function leaveBalances(Request $request): void {
            Response::json($this->leaveBalances->forEmployeeYear(
                (int) $request->user['employee_id'],
                (int) $request->input('year', date('Y'))
            ));
        }

        /** GET /api/me/leave/requests */
        public function leaveRequests(Request $request): void {
            Response::json($this->leaveRequests->forEmployee(
                (int) $request->user['employee_id']
            ));
        }

        /** POST /api/me/leave/requests */
        public function fileLeave(Request $request): void {
            $data = $this->validated($request, [
                'leave_type_id' => 'required|integer',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'reason' => 'nullable|string|max:500',
            ]);

            try {
                $result = $this->leaveService->fileRequest(
                    (int) $request->user['employee_id'],
                    (int) $data['leave_type_id'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['reason'] ?? null
                );
            } catch (\InvalidArgumentException $e) {
                Response::error($e->getMessage(), 422);
            }

            Response::json($result, 201);
        }

        /** GET /api/me/payslips */
        public function payslips(Request $request): void {
            Response::json($this->payslips->forEmployee(
                (int) $request->user['employee_id']
            ));
        }

        /** GET /api/me/goals */
        public function goals(Request $request): void {
            Response::json($this->goals->forEmployee(
                (int) $request->user['employee_id'],
                $request->input('cycle_id') ? (int) $request->input('cycle_id') : null
            ));
        }

        /** GET /api/me/evaluations?cycle_id= */
        public function evaluations(Request $request): void {
            $cycleId = $request->input('cycle_id');
            if (!$cycleId) Response::error('cycle_id is required.', 422);

            Response::json($this->evaluations->summary(
                (int) $cycleId,
                (int) $request->user['employee_id']
            ));
        }

        /** GET /api/me/onboarding */
        public function onboarding(Request $request): void {
            Response::json($this->onboardingTasks->forEmployee(
                (int) $request->user['employee_id']
            ));
        }
    }
