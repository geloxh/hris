<?php

    namespace App\Modules\Learning;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class LearningController extends Controller {
        private CourseModel $courses;
        private EnrollmentModel $enrollments;
        private CertificationModel $certifications;
        private LearningService $service;

        public function __construct() {
            $this->courses = new CourseModel();
            $this->enrollments = new EnrollmentModel();
            $this->certifications = new CertificationModel();
            $this->service  = new LearningService();
        }

        // ── Courses ── //

        /** GET /api/learning/courses */
        public function courses(Request $request): void {
            Response::json($this->courses->withEnrollmentCount());
        }

        /** GET /api/learning/courses/{id} */
        public function showCourse(Request $request): void {
            $course = $this->courses->find((int) $request->param('id'));
            if (!$course) Response::error('Course not found.', 404);
            Response::json($course);
        }

        /** POST /api/learning/courses */
        public function storeCourse(Request $request): void {
            $data = $this->validated($request, [
                'title' => 'required|string|max:200',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'duration_hours' => 'nullable|numeric',
            ]);
            Response::json($this->courses->create($data), 201);
        }

        /** PUT /api/learning/courses/{id} */
        public function updateCourse(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->courses->find($id)) Response::error('Course not found.', 404);

            $data = $this->validated($request, [
                'title' => 'nullable|string|max:200',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'duration_hours' => 'nullable|numeric',
                'is_active' => 'nullable|integer',
            ]);
            Response::json($this->courses->update($id, array_filter($data, fn($v) => $v !== null)));
        }

        // ── Enrollments ── // 

        /** GET /api/learning/courses/{id}/enrollments */
        public function courseEnrollments(Request $request): void {
            Response::json($this->enrollments->forCourse((int) $request->param('id')));
        }

        /** POST /api/learning/courses/{id}/enroll */
        public function enroll(Request $request): void {
            $data = $this->validated($request, [
                'employee_id' => 'nullable|integer',
            ]);

            // HR can enroll any employee; employee enrolls themselves
            $employeeId = !empty($data['employee_id'])
                ? (int) $data['employee_id']
                : (int) $request->user['employee_id'];

            try {
                $enrollment = $this->service->enroll($employeeId, (int) $request->param('id'));
            } catch (\InvalidArgumentException $e) {
                Response::error($e->getMessage(), 422);
            }

            Response::json($enrollment, 201);
        }

        /** PATCH /api/learning/enrollments/{id}/progress */
        public function updateProgress(Request $request): void {
            $data = $this->validated($request, [
                'progress' => 'required|integer',
            ]);

            try {
                $enrollment = $this->service->updateProgress(
                    (int) $request->param('id'),
                    (int) $data['progress']
                );
            } catch (\InvalidArgumentException $e) {
                Response::error($e->getMessage(), 422);
            }

            Response::json($enrollment);
        }

        /** GET /api/learning/my-courses */
        public function myCourses(Request $request): void {
            Response::json($this->enrollments->forEmployee(
                (int) $request->user['employee_id']
            ));
        }

        // ── Certifications ── //

        /** GET /api/learning/certifications/{employee_id} */
        public function certifications(Request $request): void {
            Response::json($this->certifications->forEmployee(
                (int) $request->param('employee_id')
            ));
        }

        /** GET /api/learning/certifications/expiring?days= */
        public function expiring(Request $request): void {
            Response::json($this->certifications->expiringSoon(
                (int) $request->input('days', 30)
            ));
        }

        /** POST /api/learning/certifications */
        public function storeCertification(Request $request): void {
            $data = $this->validated($request, [
                'employee_id' => 'nullable|integer',
                'title' => 'required|string|max:200',
                'issuer' => 'nullable|string|max:150',
                'issued_date' => 'required|date',
                'expiry_date' => 'nullable|date',
            ]);

            $data['employee_id'] = !empty($data['employee_id'])
                ? (int) $data['employee_id']
                : (int) $request->user['employee_id'];

            Response::json($this->certifications->create($data), 201);
        }

        /** DELETE /api/learning/certifications/{id} */
        public function deleteCertification(Request $request): void {
            $cert = $this->certifications->find((int) $request->param('id'));
            if (!$cert) Response::error('Certification not found.', 404);
            $this->certifications->delete((int) $cert['id']);
            Response::noContent();
        }
    }
