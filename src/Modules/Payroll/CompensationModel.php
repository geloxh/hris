<?php

    namespace App\Modules\Payroll;

    use App\Core\Model;

    class CompensationModel extends Model {
        protected string $table = 'compensations';
        protected array $fillable = ['employee_id', 'monthly_salary', 'effective_date'];
    }
