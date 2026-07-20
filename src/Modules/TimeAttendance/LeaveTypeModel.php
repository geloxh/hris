<?php

    namespace App\Modules\TimeAttendance;

    use App\Core\Model;

    class LeaveTypeModel extends Model {
        protected string $table = 'leave_types';
        protected array $fillable = ['name', 'default_days_per_year', 'is_paid'];
    }
