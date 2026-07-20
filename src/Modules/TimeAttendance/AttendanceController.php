<?php

namespace App\Modules\TimeAttendance;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class AttendanceController extends Controller
{
    private AttendanceModel $model;

    public function __construct()
    {
        $this->model = new AttendanceModel();
    }

    /** POST /api/attendance/clock-in - self-service, uses the logged-in employee */
    public function clockIn(Request $request): void
    {
        $employeeId = (int) $request->user['employee_id'];
        $today = date('Y-m-d');

        $existing = $this->model->findForEmployeeOnDate($employeeId, $today);
        if ($existing && $existing['time_in']) {
            Response::error('Already clocked in today.', 409);
        }

        $status = (int) date('G') > 9 ? 'late' : 'present';

        if ($existing) {
            $record = $this->model->update((int) $existing['id'], [
                'time_in' => date('H:i:s'),
                'status' => $status,
            ]);
        } else {
            $record = $this->model->create([
                'employee_id' => $employeeId,
                'work_date' => $today,
                'time_in' => date('H:i:s'),
                'status' => $status,
            ]);
        }

        Response::json($record, 201);
    }

    /** POST /api/attendance/clock-out */
    public function clockOut(Request $request): void
    {
        $employeeId = (int) $request->user['employee_id'];
        $today = date('Y-m-d');

        $existing = $this->model->findForEmployeeOnDate($employeeId, $today);
        if (!$existing || !$existing['time_in']) {
            Response::error('You have not clocked in today.', 409);
        }
        if ($existing['time_out']) {
            Response::error('Already clocked out today.', 409);
        }

        $record = $this->model->update((int) $existing['id'], ['time_out' => date('H:i:s')]);
        Response::json($record);
    }

    /** GET /api/attendance?employee_id=&from=&to= */
    public function index(Request $request): void
    {
        $employeeId = (int) ($request->input('employee_id') ?? $request->user['employee_id']);
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-d'));

        Response::json($this->model->forEmployee($employeeId, $from, $to));
    }

    /** POST /api/attendance/manual - HR/manager correction */
    public function storeManual(Request $request): void
    {
        $data = $this->validated($request, [
            'employee_id' => 'required|integer',
            'work_date' => 'required|date',
            'time_in' => 'nullable|string',
            'time_out' => 'nullable|string',
            'status' => 'required|in:present,late,absent,on-leave,half-day',
            'notes' => 'nullable|string',
        ]);

        $existing = $this->model->findForEmployeeOnDate((int) $data['employee_id'], $data['work_date']);
        $record = $existing
            ? $this->model->update((int) $existing['id'], $data)
            : $this->model->create($data);

        Response::json($record, $existing ? 200 : 201);
    }
}
