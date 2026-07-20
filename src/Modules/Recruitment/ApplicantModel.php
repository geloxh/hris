<?php

    namespace App\Modules\Recruitment;

    use App\Core\Model;

    class ApplicantModel extends Model {
        protected string $table = 'applicants';
        protected array $fillable = [
            'job_posting_id', 'first_name', 'last_name', 'email',
            'phone', 'resume_path', 'stage', 'notes',
        ];

        public function forPosting(int $jobPostingId, ?string $stage = null): array {
            $sql = 'SELECT * FROM applicants WHERE job_posting_id = :job_posting_id';
            $params = ['job_posting_id' => $jobPostingId];

            if ($stage) {
                $sql .= ' AND stage = :stage';
                $params['stage'] = $stage;
            }

            return $this->db->select($sql . ' ORDER BY created_at ASC', $params);
        }
    }
