<?php

namespace App\Modules\TimeAttendance;

use App\Core\Database;

class LeaveService
{
    private LeaveRequestModel $requests;
    private LeaveBalanceModel $balances;
    private LeaveTypeModel $types;
    private Database $db;

    public function __construct()
    {
        $this->requests = new LeaveRequestModel();
        $this->balances = new LeaveBalanceModel();
        $this->types = new LeaveTypeModel();
        $this->db = Database::getInstance();
    }

    /**
     * Counts weekdays inclusive of both ends. Simple and predictable; swap in a
     * holiday-calendar lookup here later without touching any calling code.
     */
    public function countBusinessDays(string $start, string $end): float
    {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $endDate->modify('+1 day'); // make the range inclusive

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        $days = 0;
        foreach ($period as $date) {
            if ((int) $date->format('N') < 6) { // Mon-Fri
                $days++;
            }
        }
        return (float) $days;
    }

    public function fileRequest(int $employeeId, int $leaveTypeId, string $start, string $end, ?string $reason): array
    {
        if (strtotime($end) < strtotime($start)) {
            throw new \InvalidArgumentException('End date cannot be before start date.');
        }

        $days = $this->countBusinessDays($start, $end);
        $year = (int) date('Y', strtotime($start));

        $leaveType = $this->types->find($leaveTypeId);
        if (!$leaveType) {
            throw new \InvalidArgumentException('Invalid leave type.');
        }

        $balance = $this->balances->findOrCreate($employeeId, $leaveTypeId, $year, (float) $leaveType['default_days_per_year']);
        $available = $balance['allocated_days'] + $balance['carried_over_days'] - $balance['used_days'];

        if ($days > $available) {
            throw new \InvalidArgumentException("Insufficient leave balance. Available: {$available} day(s), requested: {$days}.");
        }

        return $this->requests->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $start,
            'end_date' => $end,
            'days_requested' => $days,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function approve(int $requestId, int $approverUserId): array
    {
        return $this->db->transaction(function () use ($requestId, $approverUserId) {
            $request = $this->requests->find($requestId);
            if (!$request || $request['status'] !== 'pending') {
                throw new \InvalidArgumentException('Request is not pending approval.');
            }

            $year = (int) date('Y', strtotime($request['start_date']));
            $leaveType = $this->types->find((int) $request['leave_type_id']);
            $balance = $this->balances->findOrCreate(
                (int) $request['employee_id'],
                (int) $request['leave_type_id'],
                $year,
                (float) $leaveType['default_days_per_year']
            );

            $this->balances->incrementUsedDays((int) $balance['id'], (float) $request['days_requested']);

            return $this->requests->update($requestId, [
                'status' => 'approved',
                'approved_by' => $approverUserId,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
        });
    }

    public function reject(int $requestId, int $approverUserId): array
    {
        $request = $this->requests->find($requestId);
        if (!$request || $request['status'] !== 'pending') {
            throw new \InvalidArgumentException('Request is not pending approval.');
        }

        return $this->requests->update($requestId, [
            'status' => 'rejected',
            'approved_by' => $approverUserId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function cancel(int $requestId, int $employeeId): array
    {
        return $this->db->transaction(function () use ($requestId, $employeeId) {
            $request = $this->requests->find($requestId);
            if (!$request || (int) $request['employee_id'] !== $employeeId) {
                throw new \InvalidArgumentException('Leave request not found.');
            }
            if (!in_array($request['status'], ['pending', 'approved'], true)) {
                throw new \InvalidArgumentException('This request can no longer be cancelled.');
            }

            if ($request['status'] === 'approved') {
                $year = (int) date('Y', strtotime($request['start_date']));
                $balance = $this->balances->whereOne([
                    'employee_id' => $employeeId,
                    'leave_type_id' => $request['leave_type_id'],
                    'year' => $year,
                ]);
                if ($balance) {
                    $this->balances->decrementUsedDays((int) $balance['id'], (float) $request['days_requested']);
                }
            }

            return $this->requests->update($requestId, ['status' => 'cancelled']);
        });
    }
}
