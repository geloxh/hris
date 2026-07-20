<?php

    namespace App\Modules\Analytics;

    use App\Core\Database;

    class AnalyticsService {
        private Database $db;

        public function __construct() {
            $this->db = Database::getInstance();
        }

        public function headcountByDepartment(): array {
            return $this->db->select(
                "SELECT d.name AS department, COUNT(e.id) AS headcount
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE e.employment_status = 'active' AND e.deleted_at IS NULL
                GROUP BY d.id, d.name
                ORDER BY headcount DESC"
            );
        }

        public function headcountByEmploymentType(): array {
            return $this->db->select(
                "SELECT employment_type, COUNT(*) AS headcount
                FROM employees
                WHERE employment_status = 'active' AND deleted_at IS NULL
                GROUP BY employment_type"
            );
        }

        public function turnoverRate(string $from, string $to): array {
            $terminated = (int) $this->db->selectOne(
                "SELECT COUNT(*) AS cnt FROM employees
                WHERE employment_status = 'terminated'
                AND updated_at BETWEEN :from AND :to
                AND deleted_at IS NULL",
                ['from' => $from, 'to' => $to]
            )['cnt'];

            $avgHeadcount = (int) $this->db->selectOne(
                "SELECT COUNT(*) AS cnt FROM employees
                WHERE employment_status = 'active' AND deleted_at IS NULL"
            )['cnt'];

            return [
                'period_start' => $from,
                'period_end' => $to,
                'terminated' => $terminated,
                'avg_headcount' => $avgHeadcount,
                'turnover_rate' => $avgHeadcount > 0
                    ? round(($terminated / $avgHeadcount) * 100, 2)
                    : 0,
            ];
        }

        public function attendanceSummary(string $from, string $to): array {
            return $this->db->select(
                "SELECT status, COUNT(*) AS count
                FROM attendance
                WHERE work_date BETWEEN :from AND :to
                GROUP BY status",
                ['from' => $from, 'to' => $to]
            );
        }

        public function leaveSummary(int $year): array {
            return $this->db->select(
                "SELECT lt.name AS leave_type,
                        SUM(lb.allocated_days) AS total_allocated,
                        SUM(lb.used_days) AS total_used,
                        SUM(lb.allocated_days - lb.used_days) AS total_remaining
                FROM leave_balances lb
                JOIN leave_types lt ON lt.id = lb.leave_type_id
                WHERE lb.year = :year
                GROUP BY lt.id, lt.name
                ORDER BY total_used DESC",
                ['year' => $year]
            );
        }

        public function payrollSummary(int $year): array {
            return $this->db->select(
                "SELECT pr.period_start, pr.period_end, pr.status,
                        COUNT(ps.id) AS employee_count,
                        SUM(ps.gross_pay) AS total_gross,
                        SUM(ps.total_deductions) AS total_deductions,
                        SUM(ps.net_pay) AS total_net
                FROM payroll_runs pr
                LEFT JOIN payslips ps ON ps.payroll_run_id = pr.id
                WHERE YEAR(pr.period_start) = :year
                GROUP BY pr.id
                ORDER BY pr.period_start ASC",
                ['year' => $year]
            );
        }

        public function recruitmentFunnel(int $jobPostingId): array {
            return $this->db->select(
                "SELECT stage, COUNT(*) AS count
                FROM applicants
                WHERE job_posting_id = :id
                GROUP BY stage
                ORDER BY FIELD(stage,'applied','screening','interview','offer','hired','rejected')",
                ['id' => $jobPostingId]
            );
        }

        public function performanceSummary(int $cycleId): array {
            return $this->db->select(
                "SELECT
                    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                    d.name AS department,
                    AVG(ev.overall_rating) AS avg_rating,
                    COUNT(ev.id) AS evaluation_count
                FROM evaluations ev
                JOIN employees e ON e.id = ev.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE ev.review_cycle_id = :cycle_id AND ev.status = 'submitted'
                GROUP BY ev.employee_id
                ORDER BY avg_rating DESC",
                ['cycle_id' => $cycleId]
            );
        }

        public function upcomingBirthdays(int $days = 30): array {
            return $this->db->select(
                "SELECT first_name, last_name, birth_date,
                        DATE_FORMAT(birth_date, '%m-%d') AS birthday
                FROM employees
                WHERE employment_status = 'active'
                AND deleted_at IS NULL
                AND DATE_FORMAT(birth_date, '%m-%d')
                    BETWEEN DATE_FORMAT(NOW(), '%m-%d')
                    AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL :days DAY), '%m-%d')
                ORDER BY birthday ASC",
                ['days' => $days]
            );
        }

        public function newHires(string $from, string $to): array {
            return $this->db->select(
                "SELECT e.first_name, e.last_name, e.hire_date,
                        d.name AS department, p.title AS position
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                WHERE e.hire_date BETWEEN :from AND :to
                AND e.deleted_at IS NULL
                ORDER BY e.hire_date DESC",
                ['from' => $from, 'to' => $to]
            );
        }
    }
