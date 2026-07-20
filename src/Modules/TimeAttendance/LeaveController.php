<?php

namespace App\Modules\TimeAttendance;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class LeaveController extends Controller
{
    private LeaveRequestModel $requests;
    private LeaveBalanceModel $balances;
    private LeaveTypeModel $types;
    private LeaveService $service;

    public function __construct()
    {
        $this->requests = new LeaveRequestModel();
        $this->balances = new LeaveBalanceModel();
        $this->types = new LeaveTypeModel();
        $this->service = new LeaveService();
    }

    /** GET /api/leave-types */
    public function types(Request $request): void
    {
        Response::json($this->types->all(['name ASC']));
    }

    /** GET /api/leave/balances?employee_id=&year= */
    public function balances(Request $request): void
    {
        $employeeId = (int) ($request->input('employee_id') ?? $request->user['employee_id']);
        $year = (int) $request->input('year', date('Y'));
        Response::json($this->balances->forEmployeeYear($employeeId, $year));
    }

    /** GET /api/leave/requests - self-service: my own requests */
    public function myRequests(Request $request): void
    {
        Response::json($this->requests->forEmployee((int) $request->user['employee_id']));
    }

    /** GET /api/leave/requests/pending - HR/manager approval queue */
    public function pending(Request $request): void
    {
        Response::json($this->requests->pending());
    }

    /** POST /api/leave/requests */
    public function store(Request $request): void
    {
        $data = $this->validated($request, [
            'leave_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $leaveRequest = $this->service->fileRequest(
                (int) $request->user['employee_id'],
                (int) $data['leave_type_id'],
                $data['start_date'],
                $data['end_date'],
                $data['reason'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        }

        Response::json($leaveRequest, 201);
    }

    /** POST /api/leave/requests/{id}/approve */
    public function approve(Request $request): void
    {
        try {
            $result = $this->service->approve((int) $request->param('id'), (int) $request->user['id']);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        }
        Response::json($result);
    }

    /** POST /api/leave/requests/{id}/reject */
    public function reject(Request $request): void
    {
        try {
            $result = $this->service->reject((int) $request->param('id'), (int) $request->user['id']);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        }
        Response::json($result);
    }

    /** POST /api/leave/requests/{id}/cancel */
    public function cancel(Request $request): void
    {
        try {
            $result = $this->service->cancel((int) $request->param('id'), (int) $request->user['employee_id']);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        }
        Response::json($result);
    }
}
