<?php

    namespace App\Modules\Payroll;

    use App\Core\Database;
    use App\Modules\Employee\EmployeeModel;

    class PayrollService {
        private PayrollRunModel $runs;
        private PayslipModel $payslips;
        private DeductionModel $deductions;
        private BenefitModel $benefits;
        private CompensationModel $compensations;
        private EmployeeModel $employees;
        private Database $db;

        public function __construct() {
            $this->runs = new PayrollRunModel();
            $this->payslips = new PayslipModel();
            $this->deductions = new DeductionModel();
            $this->benefits = new BenefitModel();
            $this->compensations = new CompensationModel();
            $this->employees = new EmployeeModel();
            $this->db = Database::getInstance();
        }

        public function processRun(int $runId, int $processedByUserId): array {
            return $this->db->transaction(function () use ($runId, $processedByUserId) {
                $run = $this->runs->find($runId);
                if (!$run) throw new \InvalidArgumentException('Payroll run not found.');
                if ($run['status'] === 'locked') throw new \InvalidArgumentException('Payroll run is already locked.');

                $this->runs->update($runId, ['status' => 'processing']);

                $employees = $this->employees->where(['employment_status' => 'active']);
                $workingDaysInPeriod = $this->countBusinessDays($run['period_start'], $run['period_end']);

                foreach ($employees as $employee) {
                    $this->generatePayslip($run, $employee, $workingDaysInPeriod);
                }

                return $this->runs->update($runId, [
                    'status' => 'completed',
                    'processed_by' => $processedByUserId,
                    'processed_at' => date('Y-m-d H:i:s'),
                ]);
            });
        }

        private function generatePayslip(array $run, array $employee, int $workingDaysInPeriod): void {
            $compensation = $this->compensations->whereOne(['employee_id' => $employee['id']]);
            $monthlySalary = $compensation ? (float) $compensation['monthly_salary'] : 0.0;

            // count actual days present from attendance
            $absentDays = $this->countAbsentDays((int) $employee['id'], $run['period_start'], $run['period_end']);
            $daysPresent = max(0, $workingDaysInPeriod - $absentDays);

            $dailyRate = $workingDaysInPeriod > 0 ? $monthlySalary / $workingDaysInPeriod : 0;
            $basicPay = round($dailyRate * $daysPresent, 2);

            // add active benefits (allowances)
            $activeBenefits = $this->benefits->activeForEmployee((int) $employee['id'], $run['period_end']);
            $benefitsTotal = array_sum(array_column($activeBenefits, 'amount'));
            $grossPay = round($basicPay + $benefitsTotal, 2);

            // compute statutory deductions
            $deductionRows = $this->computeDeductions($grossPay);
            $totalDeductions = array_sum(array_column($deductionRows, 'amount'));
            $netPay = round($grossPay - $totalDeductions, 2);

            $payslip = $this->payslips->create([
                'payroll_run_id' => $run['id'],
                'employee_id' => $employee['id'],
                'basic_pay' => $basicPay,
                'gross_pay' => $grossPay,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'working_days' => $workingDaysInPeriod,
                'absent_days' => $absentDays,
            ]);

            foreach ($deductionRows as $row) {
                $this->deductions->create(['payslip_id' => $payslip['id']] + $row);
            }
        }

        /**
         * Statutory deduction table (PH-based, simplified).
         * Replace with actual bracket tables per your jurisdiction.
         */
        private function computeDeductions(float $grossPay): array {
            $sss = min(1125.00, round($grossPay * 0.045, 2));
            $philhealth = round($grossPay * 0.02, 2);
            $pagibig = min(100.00, round($grossPay * 0.02, 2));
            $tax  = $this->withholdingTax($grossPay - $sss - $philhealth - $pagibig);

            return [
                ['type' => 'sss', 'label' => 'SSS', 'amount' => $sss],
                ['type' => 'philhealth', 'label' => 'PhilHealth', 'amount' => $philhealth],
                ['type' => 'pagibig', 'label' => 'Pag-IBIG', 'amount' => $pagibig],
                ['type' => 'tax', 'label' => 'Withholding Tax', 'amount' => $tax],
            ];
        }

        /** Simplified monthly withholding tax (TRAIN Law brackets). */
        private function withholdingTax(float $taxableIncome): float {
            if ($taxableIncome <= 20833) return 0;
            if ($taxableIncome <= 33332) return round(($taxableIncome - 20833) * 0.20, 2);
            if ($taxableIncome <= 66666) return round(2500 + ($taxableIncome - 33333) * 0.25, 2);
            if ($taxableIncome <= 166666) return round(10833 + ($taxableIncome - 66667) * 0.30, 2);
            if ($taxableIncome <= 666666) return round(40833 + ($taxableIncome - 166667) * 0.32, 2);
            return round(200833 + ($taxableIncome - 666667) * 0.35, 2);
        }

        private function countAbsentDays(int $employeeId, string $from, string $to): float {
            $row = $this->db->selectOne(
                "SELECT COUNT(*) as cnt FROM attendance
                WHERE employee_id = :employee_id
                AND work_date BETWEEN :from AND :to
                AND status = 'absent'",
                [ 'employee_id' => $employeeId, 'from' => $from, 'to' => $to ]
            );
            return (float) ($row['cnt'] ?? 0);
        }

        private function countBusinessDays(string $start, string $end): int {
            $d = new \DateTime($start);
            $end = new \DateTime($end);
            $end->modify('+1 day');
            $days = 0;
            foreach (new \DatePeriod($d, new \DateInterval('P1D'), $end) as $date) {
                if ((int) $date->format('N') < 6) $days++;
            }
            return $days;
        }
    }
