<?php

namespace App\Modules\TimeAttendance;

use App\Core\Model;

class AttendanceModel extends Model
{
    protected string $table = 'attendance';
    protected array $fillable = ['employee_id', 'work_date', 'time_in', 'time_out', 'status', 'notes'];

    public function findForEmployeeOnDate(int $employeeId, string $date): ?array
    {
        return $this->whereOne(['employee_id' => $employeeId, 'work_date' => $date]);
    }

    public function forEmployee(int $employeeId, string $from, string $to): array
    {
        return $this->db->select(
            'SELECT * FROM attendance
             WHERE employee_id = :employee_id AND work_date BETWEEN :from AND :to
             ORDER BY work_date DESC',
            ['employee_id' => $employeeId, 'from' => $from, 'to' => $to]
        );
    }
}
