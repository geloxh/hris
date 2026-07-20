<?php

    namespace App\Modules\Learning;

    use App\Core\Model;

    class EnrollmentModel extends Model {
        protected string $table = 'course_enrollments';
        protected bool $auditable = true;
        protected array $fillable = ['course_id', 'employee_id', 'status', 'progress', 'enrolled_at', 'completed_at'];

        public function forEmployee(int $employeeId): array {
            return $this->db->select(
                "SELECT ce.*, c.title AS course_title, c.category, c.duration_hours
                FROM course_enrollments ce
                JOIN courses c ON c.id = ce.course_id
                WHERE ce.employee_id = :employee_id
                ORDER BY ce.enrolled_at DESC",
                ['employee_id' => $employeeId]
            );
        }

        public function forCourse(int $courseId): array {
            return $this->db->select(
                "SELECT ce.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                        e.employee_number
                FROM course_enrollments ce
                JOIN employees e ON e.id = ce.employee_id
                WHERE ce.course_id = :course_id
                ORDER BY ce.enrolled_at ASC",
                ['course_id' => $courseId]
            );
        }
    }
