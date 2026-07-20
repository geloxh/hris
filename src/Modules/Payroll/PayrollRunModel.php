<?php

    namespace App\Modules\Payroll;

    use App\Core\Model;

    class PayrollRunModel extends Model {
        protected string $table = 'payroll_runs';
        protected array $fillable = ['period_start', 'period_end', 'status', 'processed_by', 'processed_at'];

        public function withPayslips(int $runId): ?array {
            $run = $this->find($runId);
            if (!$run) return null;

            $run['payslips'] = $this->db->select(
                'SELECT ps.*, CONCAT(e.first_name, " ", e.last_name) AS employee_name, e.employee_number
                FROM payslips ps
                JOIN employees e ON e.id = ps.employee_id
                WHERE ps.payroll_run_id = :run_id
                ORDER BY e.last_name ASC',
                ['run_id' => $runId]
            );

            return $run;
        }
    }
