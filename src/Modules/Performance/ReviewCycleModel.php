<?php

    namespace App\Modules\Performance;

    use App\Core\Model;

    class ReviewCycleModel extends Model {
        protected string $table = 'review_cycles';
        protected array $fillable = ['name', 'type', 'start_date', 'end_date', 'status'];

        public function withStats(int $id): ?array {
            $cycle = $this->find($id);
            if (!$cycle) return null;

            $cycle['total_evaluations'] = (int) $this->db->selectOne(
                'SELECT COUNT(*) as cnt FROM evaluations WHERE review_cycle_id = :id',
                ['id' => $id]
            )['cnt'];

            $cycle['submitted_evaluations'] = (int) $this->db->selectOne(
                'SELECT COUNT(*) as cnt FROM evaluations WHERE review_cycle_id = :id AND status = "submitted"',
                ['id' => $id]
            )['cnt'];

            $cycle['avg_rating'] = (float) ($this->db->selectOne(
                'SELECT AVG(overall_rating) as avg FROM evaluations
                WHERE review_cycle_id = :id AND status = "submitted" AND overall_rating IS NOT NULL',
                ['id' => $id]
            )['avg'] ?? 0);

            return $cycle;
        }
    }
