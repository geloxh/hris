<?php

namespace App\Modules\TimeAttendance;

use App\Core\Model;

class LeaveBalanceModel extends Model
{
    protected string $table = 'leave_balances';
    protected array $fillable = [
        'employee_id', 'leave_type_id', 'year', 'allocated_days', 'used_days', 'carried_over_days',
    ];

    public function forEmployeeYear(int $employeeId, int $year): array
    {
        return $this->db->select(
            'SELECT lb.*, lt.name AS leave_type_name, lt.is_paid
             FROM leave_balances lb
             JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.employee_id = :employee_id AND lb.year = :year',
            ['employee_id' => $employeeId, 'year' => $year]
        );
    }

    public function findOrCreate(int $employeeId, int $leaveTypeId, int $year, float $allocated): array
    {
        $existing = $this->whereOne([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
        ]);

        if ($existing) {
            return $existing;
        }

        return $this->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'allocated_days' => $allocated,
            'used_days' => 0,
            'carried_over_days' => 0,
        ]);
    }

    public function incrementUsedDays(int $balanceId, float $days): void
    {
        $this->db->execute(
            'UPDATE leave_balances SET used_days = used_days + :days WHERE id = :id',
            ['days' => $days, 'id' => $balanceId]
        );
    }

    public function decrementUsedDays(int $balanceId, float $days): void
    {
        $this->db->execute(
            'UPDATE leave_balances SET used_days = GREATEST(0, used_days - :days) WHERE id = :id',
            ['days' => $days, 'id' => $balanceId]
        );
    }
}
