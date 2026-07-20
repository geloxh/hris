<?php

    namespace App\Modules\Recruitment;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class RecruitmentController extends Controller {
        private JobPostingModel $postings;
        private ApplicantModel $applicants;
        private OnboardingTaskModel $tasks;

        public function __construct() {
            $this->postings = new JobPostingModel();
            $this->applicants = new ApplicantModel();
            $this->tasks = new OnboardingTaskModel();
        }

        /** GET /api/recruitment/postings */
        public function postings(Request $request): void {
            $status = $request->input('status');
            $rows = $status
                ? $this->postings->where(['status' => $status], ['created_at DESC'])
                : $this->postings->all(['created_at DESC']);
            Response::json($rows);
        }

        /** GET /api/recruitment/postings/{id} */
        public function showPosting(Request $request): void {
            $posting = $this->postings->withCounts((int) $request->param('id'));
            if (!$posting) Response::error('Job posting not found.', 404);
            Response::json($posting);
        }

        /** POST /api/recruitment/postings */
        public function storePosting(Request $request): void {
            $data = $this->validated($request, [
                'title' => 'required|string|max:150',
                'department_id' => 'nullable|integer',
                'position_id' => 'nullable|integer',
                'description' => 'nullable|string',
                'status' => 'nullable|in:draft,open,closed',
                'posted_at' => 'nullable|date',
                'closes_at' => 'nullable|date',
            ]);
            Response::json($this->postings->create($data), 201);
        }

        /** PUT /api/recruitment/postings/{id} */
        public function updatePosting(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->postings->find($id)) Response::error('Job posting not found.', 404);

            $data = $this->validated($request, [
                'title' => 'nullable|string|max:150',
                'department_id' => 'nullable|integer',
                'position_id' => 'nullable|integer',
                'description' => 'nullable|string',
                'status' => 'nullable|in:draft,open,closed',
                'posted_at' => 'nullable|date',
                'closes_at' => 'nullable|date',
            ]);
            Response::json($this->postings->update($id, array_filter($data, fn($v) => $v !== null)));
        }

        /** GET /api/recruitment/postings/{id}/applicants */
        public function applicants(Request $request): void {
            Response::json($this->applicants->forPosting(
                (int) $request->param('id'),
                $request->input('stage')
            ));
        }

        /** POST /api/recruitment/postings/{id}/applicants */
        public function storeApplicant(Request $request): void {
            $data = $this->validated($request, [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:150',
                'phone' => 'nullable|string|max:30',
                'notes' => 'nullable|string',
            ]);
            $data['job_posting_id'] = (int) $request->param('id');
            Response::json($this->applicants->create($data), 201);
        }

        /** PATCH /api/recruitment/applicants/{id}/stage */
        public function updateStage(Request $request): void {
            $id = (int) $request->param('id');
            $data = $this->validated($request, [
                'stage' => 'required|in:applied,screening,interview,offer,hired,rejected',
            ]);
            $applicant = $this->applicants->find($id);
            if (!$applicant) Response::error('Applicant not found.', 404);
            Response::json($this->applicants->update($id, $data));
        }

        /** GET /api/recruitment/onboarding/{employee_id} */
        public function onboardingTasks(Request $request): void {
            Response::json($this->tasks->forEmployee((int) $request->param('employee_id')));
        }

        /** POST /api/recruitment/onboarding */
        public function storeTask(Request $request): void {
            $data = $this->validated($request, [
                'employee_id' => 'required|integer',
                'title' => 'required|string|max:150',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date',
            ]);
            Response::json($this->tasks->create($data), 201);
        }

        /** PATCH /api/recruitment/onboarding/{id}/complete */
        public function completeTask(Request $request): void {
            $task = $this->tasks->find((int) $request->param('id'));
            if (!$task) Response::error('Task not found.', 404);
            Response::json($this->tasks->update((int) $task['id'], [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]));
        }
    }
