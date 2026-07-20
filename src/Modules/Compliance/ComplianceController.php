<?php

    namespace App\Modules\Compliance;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class ComplianceController extends Controller {
        private PolicyModel $policies;
        private AcknowledgmentModel $acknowledgments;
        private AuditLogModel $auditLogs;

        public function __construct() {
            $this->policies = new PolicyModel();
            $this->acknowledgments = new AcknowledgmentModel();
            $this->auditLogs = new AuditLogModel();
        }

        // ── Policies ── //

        /** GET /api/compliance/policies */
        public function policies(Request $request): void
        {
            Response::json($this->policies->active());
        }

        /** GET /api/compliance/policies/status - shows ack status for logged-in employee */
        public function myPolicies(Request $request): void {
            Response::json($this->policies->withAcknowledgmentStatus(
                (int) $request->user['employee_id']
            ));
        }

        /** POST /api/compliance/policies */
        public function storePolicy(Request $request): void {
            $data = $this->validated($request, [
                'title' => 'required|string|max:200',
                'content' => 'required|string',
                'version' => 'nullable|string|max:20',
                'effective_date' => 'required|date',
            ]);
            Response::json($this->policies->create($data), 201);
        }

        /** PUT /api/compliance/policies/{id} */
        public function updatePolicy(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->policies->find($id)) Response::error('Policy not found.', 404);

            $data = $this->validated($request, [
                'title' => 'nullable|string|max:200',
                'content' => 'nullable|string',
                'version' => 'nullable|string|max:20',
                'is_active' => 'nullable|integer',
                'effective_date' => 'nullable|date',
            ]);
            Response::json($this->policies->update($id, array_filter($data, fn($v) => $v !== null)));
        }

        // ── Acknowledgments ── //

        /** POST /api/compliance/policies/{id}/acknowledge */
        public function acknowledge(Request $request): void {
            $policyId = (int) $request->param('id');
            $employeeId = (int) $request->user['employee_id'];

            if (!$this->policies->find($policyId)) Response::error('Policy not found.', 404);

            $existing = $this->acknowledgments->whereOne([
                'policy_id' => $policyId,
                'employee_id' => $employeeId,
            ]);

            if ($existing) Response::error('Policy already acknowledged.', 409);

            $ack = $this->acknowledgments->create([
                'policy_id'  => $policyId,
                'employee_id' => $employeeId,
                'acknowledged_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            Response::json($ack, 201);
        }

        /** GET /api/compliance/policies/{id}/acknowledgments - who has/hasn't signed */
        public function acknowledgments(Request $request): void {
            $policyId = (int) $request->param('id');
            if (!$this->policies->find($policyId)) Response::error('Policy not found.', 404);

            Response::json([
                'acknowledged' => $this->acknowledgments->forPolicy($policyId),
                'pending' => $this->acknowledgments->pendingEmployees($policyId),
            ]);
        }

        // ── Audit Logs ── //

        /** GET /api/compliance/audit-logs?user_id=&entity_type=&entity_id=&action=&from=&to=&page= */
        public function auditLogs(Request $request): void
        {
            Response::json($this->auditLogs->search(
                [
                    'user_id' => $request->input('user_id'),
                    'entity_type' => $request->input('entity_type'),
                    'entity_id' => $request->input('entity_id'),
                    'action' => $request->input('action'),
                    'from' => $request->input('from'),
                    'to' => $request->input('to'),
                ],
                (int) $request->input('page', 1),
                (int) $request->input('per_page', 50)
            ));
        }

        /** GET /api/compliance/audit-logs/employee/{employee_id} - full trail for one employee */
        public function employeeAuditTrail(Request $request): void {
            Response::json($this->auditLogs->search(
                ['entity_id' => (int) $request->param('employee_id'), 'entity_type' => 'employees'],
                (int) $request->input('page', 1)
            ));
        }
    }
