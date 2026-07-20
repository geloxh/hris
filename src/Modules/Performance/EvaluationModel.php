<?php

    namespace App\Modules\Performance;

    use App\Core\Model;

    class EvaluationModel extends Model {
        protected string $table = 'evaluations';
        protected array $fillable = [
            'review_cycle_id', 'employee_id', 'evaluator_id',
            'type', 'overall_rating', 'comments', 'status', 'submitted_at',
        ];

        public function forCycleEmployee(int $cycleId, int $employeeId): array {
            return $this->db->select(
                'SELECT ev.*,
                        CONCAT(e.first_name, " ", e.last_name) AS evaluator_name
                FROM evaluations ev
                JOIN employees e ON e.id = ev.evaluator_id
                WHERE ev.review_cycle_id = :cycle_id AND ev.employee_id = :employee_id
                ORDER BY ev.type ASC',
                [ 'cycle_id' => $cycleId, 'employee_id' => $employeeId ]
            );
        }

        public function summary(int $cycleId, int $employeeId): array {
            $rows = $this->forCycleEmployee($cycleId, $employeeId);

            $submitted = array_filter($rows, fn($r) => $r['status'] === 'submitted' && $r['overall_rating'] !== null);
            $ratings = array_column(array_values($submitted), 'overall_rating');
            $avg = count($ratings) ? round(array_sum($ratings) / count($ratings), 2) : null;

            return [
                'evaluations' => $rows,
                'avg_rating' => $avg,
                'total' => count($rows),
                'submitted' => count($submitted),
            ];
        }
    }
