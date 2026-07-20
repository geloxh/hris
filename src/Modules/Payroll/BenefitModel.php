<?php

    namespace App\Modules\Payroll;

    use App\Core\Model;

    class BenefitModel extends Model {
        protected string $table = 'benefits';
        protected array $fillable = [ 'employee_id', 'type', 'label', 'amount', 'effective_date', 'end_date' ];

        public function activeForEmployee(int $employeeId, string $asOf): array {
            return $this->db->select(
                'SELECT * FROM benefits
                WHERE employee_id = :employee_id
                AND effective_date <= :as_of
                AND (end_date IS NULL OR end_date >= :as_of)',
                ['employee_id' => $employeeId, 'as_of' => $asOf]
            );
        }
    }
