<?php

    namespace App\Modules\Learning;

    class LearningService {
        private CourseModel $courses;
        private EnrollmentModel $enrollments;

        public function __construct() {
            $this->courses = new CourseModel();
            $this->enrollments = new EnrollmentModel();
        }

        public function enroll(int $employeeId, int $courseId): array {
            if (!$this->courses->find($courseId)) {
                throw new \InvalidArgumentException('Course not found.');
            }

            $existing = $this->enrollments->whereOne([
                'employee_id' => $employeeId,
                'course_id' => $courseId,
            ]);

            if ($existing && $existing['status'] !== 'dropped') {
                throw new \InvalidArgumentException('Already enrolled in this course.');
            }

            if ($existing) {
                return $this->enrollments->update((int) $existing['id'], [
                    'status' => 'enrolled',
                    'progress' => 0,
                    'enrolled_at' => date('Y-m-d H:i:s'),
                    'completed_at'=> null,
                ]);
            }

            return $this->enrollments->create([
                'course_id' => $courseId,
                'employee_id' => $employeeId,
                'status' => 'enrolled',
            ]);
        }

        public function updateProgress(int $enrollmentId, int $progress): array {
            $enrollment = $this->enrollments->find($enrollmentId);
            if (!$enrollment) throw new \InvalidArgumentException('Enrollment not found.');

            $progress = max(0, min(100, $progress));
            $data = ['progress' => $progress, 'status' => 'in_progress'];

            if ($progress === 100) {
                $data['status'] = 'completed';
                $data['completed_at'] = date('Y-m-d H:i:s');
            }

            return $this->enrollments->update($enrollmentId, $data);
        }
    }
