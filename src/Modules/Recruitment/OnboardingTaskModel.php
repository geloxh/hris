<?php

    namespace App\Modules\Recruitment;

    use App\Core\Model;

    class OnboardingTaskModel extends Model {
        protected string $table = 'onboarding_tasks';
        protected array $fillable = ['employee_id', 'title', 'description', 'due_date', 'status', 'completed_at'];

        public function forEmployee(int $employeeId): array {
            return $this->where(['employee_id' => $employeeId], ['due_date ASC']);
        }
    }
