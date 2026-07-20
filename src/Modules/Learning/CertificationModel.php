<?php

    namespace App\Modules\Learning;

    use App\Core\Model;

    class CertificationModel extends Model {
        protected string $table = 'certifications';
        protected bool $auditable = true;
        protected array $fillable = ['employee_id', 'title', 'issuer', 'issued_date', 'expiry_date', 'certificate_path'];

        public function forEmployee(int $employeeId): array {
            return $this->where(['employee_id' => $employeeId], ['issued_date DESC']);
        }

        public function expiringSoon(int $days = 30): array {
            return $this->db->select(
                "SELECT c.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name
                FROM certifications c
                JOIN employees e ON e.id = c.employee_id
                WHERE c.expiry_date IS NOT NULL
                AND c.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                ORDER BY c.expiry_date ASC",
                ['days' => $days]
            );
        }
    }
