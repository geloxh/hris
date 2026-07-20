<?php

    namespace App\Modules\Compliance;

    use App\Core\Model;

    class AcknowledgmentModel extends Model {
        protected string $table  = 'policy_acknowledgments';
        protected bool $auditable = true;
        protected array $fillable = ['policy_id', 'employee_id', 'acknowledged_at', 'ip_address'];

        public function forPolicy(int $policyId): array {
            return $this->db->select(
                "SELECT pa.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name
                FROM policy_acknowledgments pa
                JOIN employees e ON e.id = pa.employee_id
                WHERE pa.policy_id = :policy_id
                ORDER BY pa.acknowledged_at DESC",
                ['policy_id' => $policyId]
            );
        }

        public function pendingEmployees(int $policyId): array {
            return $this->db->select(
                "SELECT e.id, e.first_name, e.last_name, e.email, d.name AS department
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN policy_acknowledgments pa
                    ON pa.policy_id = :policy_id AND pa.employee_id = e.id
                WHERE e.employment_status = 'active'
                AND e.deleted_at IS NULL
                AND pa.id IS NULL
                ORDER BY e.last_name ASC",
                ['policy_id' => $policyId]
            );
        }
    }
