<?php

    namespace App\Modules\TimeAttendance;

    use App\Core\Model;

    class LeaveRequestModel extends Model {
        protected string $table = 'leave_requests';
        protected array $fillable = [
            'employee_id', 'leave_type_id', 'start_date', 'end_date', 'days_requested',
            'reason', 'status', 'approved_by', 'approved_at',
        ];

        public function forEmployee(int $employeeId): array {
            return $this->db->select(
                'SELECT lr.*, lt.name AS leave_type_name
                FROM leave_requests lr JOIN leave_types lt ON lt.id = lr.leave_type_id
                WHERE lr.employee_id = :employee_id
                ORDER BY lr.created_at DESC',
                ['employee_id' => $employeeId]
            );
        }

        public function pending(): array {
            return $this->db->select(
                'SELECT lr.*, lt.name AS leave_type_name,
                        CONCAT(e.first_name, " ", e.last_name) AS employee_name
                FROM leave_requests lr
                JOIN leave_types lt ON lt.id = lr.leave_type_id
                JOIN employees e ON e.id = lr.employee_id
                WHERE lr.status = "pending"
                ORDER BY lr.created_at ASC'
            );
        }
    }
