<?php

    namespace App\Modules\Recruitment;

    use App\Core\Model;

    class JobPostingModel extends Model {
        protected string $table = 'job_postings';
        protected array $fillable = [
            'title', 'department_id', 'position_id', 'description',
            'status', 'posted_at', 'closes_at',
        ];

        public function withCounts(int $id): ?array {
            return $this->db->selectOne(
                'SELECT jp.*,
                        d.name AS department_name,
                        p.title AS position_title,
                        COUNT(a.id) AS applicant_count
                FROM job_postings jp
                LEFT JOIN departments d ON d.id = jp.department_id
                LEFT JOIN positions p ON p.id = jp.position_id
                LEFT JOIN applicants a ON a.job_posting_id = jp.id
                WHERE jp.id = :id
                GROUP BY jp.id',
                ['id' => $id]
            );
        }
    }
