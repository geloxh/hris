<?php

    namespace App\Modules\Payroll;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class PayrollController extends Controller {
        private PayrollRunModel $runs;
        private PayslipModel $payslips;
        private BenefitModel $benefits;
        private CompensationModel $compensations;
        private PayrollService $service;

        public function __construct() {
            $this->runs = new PayrollRunModel();
            $this->payslips = new PayslipModel();
            $this->benefits = new BenefitModel();
            $this->compensations = new CompensationModel();
            $this->service = new PayrollService();
        }

        /** GET /api/payroll/runs */
        public function index(Request $request): void {
            Response::json($this->runs->all(['period_start DESC']));
        }

        /** POST /api/payroll/runs */
        public function store(Request $request): void {
            $data = $this->validated($request, [
                'period_start' => 'required|date',
                'period_end' => 'required|date',
            ]);
            Response::json($this->runs->create($data), 201);
        }

        /** GET /api/payroll/runs/{id} */
        public function show(Request $request): void {
            $run = $this->runs->withPayslips((int) $request->param('id'));
            if (!$run) Response::error('Payroll run not found.', 404);
            Response::json($run);
        }

        /** POST /api/payroll/runs/{id}/process */
        public function process(Request $request): void {
            try {
                $run = $this->service->processRun(
                    (int) $request->param('id'),
                    (int) $request->user['id']
                );
            } catch (\InvalidArgumentException $e) {
                Response::error($e->getMessage(), 422);
            }
            Response::json($run);
        }

        /** POST /api/payroll/runs/{id}/lock */
        public function lock(Request $request): void {
            $run = $this->runs->find((int) $request->param('id'));
            if (!$run) Response::error('Payroll run not found.', 404);
            if ($run['status'] !== 'completed') Response::error('Only completed runs can be locked.', 422);
            Response::json($this->runs->update((int) $run['id'], ['status' => 'locked']));
        }

        /** GET /api/payroll/payslips/{id} */
        public function payslip(Request $request): void {
            $payslip = $this->payslips->withDeductions((int) $request->param('id'));
            if (!$payslip) Response::error('Payslip not found.', 404);
            Response::json($payslip);
        }

        /** GET /api/payroll/my-payslips - self-service */
        public function myPayslips(Request $request): void {
            Response::json($this->payslips->forEmployee((int) $request->user['employee_id']));
        }

        /** GET /api/payroll/compensations/{employee_id} */
        public function compensation(Request $request): void {
            $comp = $this->compensations->whereOne(['employee_id' => (int) $request->param('employee_id')]);
            if (!$comp) Response::error('No compensation record found.', 404);
            Response::json($comp);
        }

        /** POST /api/payroll/compensations */
        public function storeCompensation(Request $request): void {
            $data = $this->validated($request, [
                'employee_id' => 'required|integer',
                'monthly_salary' => 'required|numeric',
                'effective_date' => 'required|date',
            ]);

            $existing = $this->compensations->whereOne(['employee_id' => $data['employee_id']]);
            $comp = $existing
                ? $this->compensations->update((int) $existing['id'], $data)
                : $this->compensations->create($data);

            Response::json($comp, $existing ? 200 : 201);
        }

        /** GET /api/payroll/benefits/{employee_id} */
        public function benefits(Request $request): void {
            Response::json($this->benefits->where(['employee_id' => (int) $request->param('employee_id')]));
        }

        /** POST /api/payroll/benefits */
        public function storeBenefit(Request $request): void {
            $data = $this->validated($request, [
                'employee_id' => 'required|integer',
                'type' => 'required|string|max:50',
                'label' => 'required|string|max:100',
                'amount' => 'required|numeric',
                'effective_date' => 'required|date',
                'end_date' => 'nullable|date',
            ]);
            Response::json($this->benefits->create($data), 201);
        }
    }
