<?php

    namespace App\Modules\Performance;

    use App\Core\Model;

    class GoalModel extends Model {
        protected string $table = 'goals';
        protected array $fillable = [
            'employee_id', 'review_cycle_id', 'title', 'description',
            'target_date', 'status', 'progress',
        ];

        public function forEmployee(int $employeeId, ?int $cycleId = null): array {
            $sql = 'SELECT g.*, rc.name AS cycle_name
                    FROM goals g
                    LEFT JOIN review_cycles rc ON rc.id = g.review_cycle_id
                    WHERE g.employee_id = :employee_id';
            $params = ['employee_id' => $employeeId];

            if ($cycleId) {
                $sql .= ' AND g.review_cycle_id = :cycle_id';
                $params['cycle_id'] = $cycleId;
            }

            return $this->db->select($sql . ' ORDER BY g.target_date ASC', $params);
        }
    }
