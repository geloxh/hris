<?php

    namespace App\Modules\Analytics;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class AnalyticsController extends Controller {
        private AnalyticsService $service;

        public function __construct() {
            $this->service = new AnalyticsService();
        }

        /** GET /api/analytics/headcount */
        public function headcount(Request $request): void {
            Response::json([
                'by_department' => $this->service->headcountByDepartment(),
                'by_employment_type' => $this->service->headcountByEmploymentType(),
            ]);
        }

        /** GET /api/analytics/turnover?from=&to= */
        public function turnover(Request $request): void {
            $from = $request->input('from', date('Y-01-01'));
            $to = $request->input('to', date('Y-m-d'));
            Response::json($this->service->turnoverRate($from, $to));
        }

        /** GET /api/analytics/attendance?from=&to= */
        public function attendance(Request $request): void {
            $from = $request->input('from', date('Y-m-01'));
            $to = $request->input('to', date('Y-m-d'));
            Response::json($this->service->attendanceSummary($from, $to));
        }

        /** GET /api/analytics/leave?year= */
        public function leave(Request $request): void {
            Response::json($this->service->leaveSummary(
                (int) $request->input('year', date('Y'))
            ));
        }

        /** GET /api/analytics/payroll?year= */
        public function payroll(Request $request): void {
            Response::json($this->service->payrollSummary(
                (int) $request->input('year', date('Y'))
            ));
        }

        /** GET /api/analytics/recruitment/{job_posting_id}/funnel */
        public function recruitmentFunnel(Request $request): void {
            Response::json($this->service->recruitmentFunnel(
                (int) $request->param('job_posting_id')
            ));
        }

        /** GET /api/analytics/performance/{cycle_id} */
        public function performance(Request $request): void {
            Response::json($this->service->performanceSummary(
                (int) $request->param('cycle_id')
            ));
        }

        /** GET /api/analytics/birthdays?days= */
        public function birthdays(Request $request): void {
            Response::json($this->service->upcomingBirthdays(
                (int) $request->input('days', 30)
            ));
        }

        /** GET /api/analytics/new-hires?from=&to= */
        public function newHires(Request $request): void {
            $from = $request->input('from', date('Y-01-01'));
            $to = $request->input('to', date('Y-m-d'));
            Response::json($this->service->newHires($from, $to));
        }
    }
