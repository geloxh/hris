<?php

    namespace App\Modules\Payroll;

    use App\Core\Model;

    class DeductionModel extends Model {
        protected string $table = 'deductions';
        protected array $fillable = ['payslip_id', 'type', 'label', 'amount'];
    }
