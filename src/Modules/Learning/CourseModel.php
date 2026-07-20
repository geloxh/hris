<?php

    namespace App\Modules\Learning;

    use App\Core\Model;

    class CourseModel extends Model {
        protected string $table   = 'courses';
        protected bool $auditable = true;
        protected array $fillable = ['title', 'description', 'category', 'duration_hours', 'is_active'];

        public function active(): array {
            return $this->where(['is_active' => 1], ['title ASC']);
        }

        public function withEnrollmentCount(): array {
            return $this->db->select(
                "SELECT c.*, COUNT(ce.id) AS enrollment_count,
                        SUM(IF(ce.status = 'completed', 1, 0)) AS completed_count
                FROM courses c
                LEFT JOIN course_enrollments ce ON ce.course_id = c.id
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY c.title ASC"
            );
        }
    }
