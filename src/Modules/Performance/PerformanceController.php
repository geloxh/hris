<?php

    namespace App\Modules\Performance;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class PerformanceController extends Controller {
        private ReviewCycleModel $cycles;
        private GoalModel $goals;
        private EvaluationModel $evaluations;

        public function __construct() {
            $this->cycles = new ReviewCycleModel();
            $this->goals = new GoalModel();
            $this->evaluations = new EvaluationModel();
        }

        // ── Review Cycles ── //

        /** GET /api/performance/cycles */
        public function cycles(Request $request): void {
            $status = $request->input('status');
            $rows = $status
                ? $this->cycles->where(['status' => $status], ['start_date DESC'])
                : $this->cycles->all(['start_date DESC']);
            Response::json($rows);
        }

        /** GET /api/performance/cycles/{id} */
        public function showCycle(Request $request): void {
            $cycle = $this->cycles->withStats((int) $request->param('id'));
            if (!$cycle) Response::error('Review cycle not found.', 404);
            Response::json($cycle);
        }

        /** POST /api/performance/cycles */
        public function storeCycle(Request $request): void {
            $data = $this->validated($request, [
                'name' => 'required|string|max:150',
                'type' => 'required|in:annual,mid-year,quarterly,probationary',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'status' => 'nullable|in:draft,active,closed',
            ]);
            Response::json($this->cycles->create($data), 201);
        }

        /** PATCH /api/performance/cycles/{id}/status */
        public function updateCycleStatus(Request $request): void {
            $id = (int) $request->param('id');
            $data = $this->validated($request, [
                'status' => 'required|in:draft,active,closed',
            ]);
            if (!$this->cycles->find($id)) Response::error('Review cycle not found.', 404);
            Response::json($this->cycles->update($id, $data));
        }

        // ── Goals ── //

        /** GET /api/performance/goals?employee_id=&cycle_id= */
        public function goals(Request $request): void {
            $employeeId = (int) ($request->input('employee_id') ?? $request->user['employee_id']);
            $cycleId = $request->input('cycle_id') ? (int) $request->input('cycle_id') : null;
            Response::json($this->goals->forEmployee($employeeId, $cycleId));
        }

        /** POST /api/performance/goals */
        public function storeGoal(Request $request): void {
            $data = $this->validated($request, [
                'title' => 'required|string|max:200',
                'description' => 'nullable|string',
                'review_cycle_id' => 'nullable|integer',
                'target_date' => 'nullable|date',
                'status' => 'nullable|in:draft,active,achieved,missed',
            ]);
            $data['employee_id'] = (int) $request->user['employee_id'];
            Response::json($this->goals->create($data), 201);
        }

        /** PATCH /api/performance/goals/{id} */
        public function updateGoal(Request $request): void {
            $id = (int) $request->param('id');
            $goal = $this->goals->find($id);
            if (!$goal) Response::error('Goal not found.', 404);

            // employees can only update their own goals unless HR/admin
            $role = $request->user['role'] ?? '';
            if (!in_array($role, ['admin', 'hr_manager'], true)) {
                if ((int) $goal['employee_id'] !== (int) $request->user['employee_id']) {
                    Response::error('Forbidden.', 403);
                }
            }

            $data = $this->validated($request, [
                'title' => 'nullable|string|max:200',
                'description' => 'nullable|string',
                'target_date' => 'nullable|date',
                'status' => 'nullable|in:draft,active,achieved,missed',
                'progress' => 'nullable|integer',
            ]);
            Response::json($this->goals->update($id, array_filter($data, fn($v) => $v !== null)));
        }

        // ── Evaluations ── //

        /** GET /api/performance/cycles/{id}/evaluations?employee_id= */
        public function evaluations(Request $request): void {
            $cycleId = (int) $request->param('id');
            $employeeId = (int) ($request->input('employee_id') ?? $request->user['employee_id']);
            Response::json($this->evaluations->summary($cycleId, $employeeId));
        }

        /** POST /api/performance/evaluations */
        public function storeEvaluation(Request $request): void {
            $data = $this->validated($request, [
                'review_cycle_id' => 'required|integer',
                'employee_id' => 'required|integer',
                'type' => 'required|in:self,manager,peer,360',
                'overall_rating' => 'nullable|numeric',
                'comments' => 'nullable|string',
            ]);
            $data['evaluator_id'] = (int) $request->user['employee_id'];

            // self-evaluation: evaluator must be the subject
            if ($data['type'] === 'self' && $data['evaluator_id'] !== (int) $data['employee_id']) {
                Response::error('Self-evaluation must be submitted by the employee themselves.', 422);
            }

            Response::json($this->evaluations->create($data), 201);
        }

        /** PATCH /api/performance/evaluations/{id} */
        public function updateEvaluation(Request $request): void {
            $id = (int) $request->param('id');
            $evaluation = $this->evaluations->find($id);
            if (!$evaluation) Response::error('Evaluation not found.', 404);
            if ($evaluation['status'] === 'submitted') Response::error('Submitted evaluations cannot be edited.', 422);

            $data = $this->validated($request, [
                'overall_rating' => 'nullable|numeric',
                'comments' => 'nullable|string',
            ]);
            Response::json($this->evaluations->update($id, array_filter($data, fn($v) => $v !== null)));
        }

        /** POST /api/performance/evaluations/{id}/submit */
        public function submitEvaluation(Request $request): void {
            $id = (int) $request->param('id');
            $evaluation = $this->evaluations->find($id);
            if (!$evaluation) Response::error('Evaluation not found.', 404);
            if ($evaluation['status'] === 'submitted') Response::error('Already submitted.', 422);

            Response::json($this->evaluations->update($id, [
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ]));
        }
    }
