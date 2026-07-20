<?php

    namespace App\Modules\Payroll;

    use App\Core\Model;

    class PayslipModel extends Model {
        protected string $table = 'payslips';
        protected array $fillable = [
            'payroll_run_id', 'employee_id', 'basic_pay', 'gross_pay',
            'total_deductions', 'net_pay', 'working_days', 'absent_days',
        ];

        public function withDeductions(int $payslipId): ?array {
            $payslip = $this->find($payslipId);
            if (!$payslip) return null;

            $payslip['deductions'] = $this->db->select(
                'SELECT * FROM deductions WHERE payslip_id = :id',
                ['id' => $payslipId]
            );

            return $payslip;
        }

        public function forEmployee(int $employeeId): array {
            return $this->db->select(
                'SELECT ps.*, pr.period_start, pr.period_end
                FROM payslips ps
                JOIN payroll_runs pr ON pr.id = ps.payroll_run_id
                WHERE ps.employee_id = :employee_id
                ORDER BY pr.period_start DESC',
                ['employee_id' => $employeeId]
            );
        }
    }
