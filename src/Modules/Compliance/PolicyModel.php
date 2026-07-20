<?php

    namespace App\Modules\Compliance;

    use App\Core\Model;

    class PolicyModel extends Model {
        protected string $table = 'policies';
        protected bool $auditable = true;
        protected array $fillable = [ 'title', 'content', 'version', 'is_active', 'effective_date' ];

        public function active(): array {
            return $this->where(['is_active' => 1], ['effective_date DESC']);
        }

        public function withAcknowledgmentStatus(int $employeeId): array {
            return $this->db->select(
                "SELECT p.*,
                        IF(pa.id IS NOT NULL, 1, 0) AS acknowledged,
                        pa.acknowledged_at
                FROM policies p
                LEFT JOIN policy_acknowledgments pa
                    ON pa.policy_id = p.id AND pa.employee_id = :employee_id
                WHERE p.is_active = 1
                ORDER BY p.effective_date DESC",
                ['employee_id' => $employeeId]
            );
        }
    }
