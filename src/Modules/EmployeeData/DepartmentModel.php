<?php

namespace App\Modules\EmployeeData;

use App\Core\Model;

class DepartmentModel extends Model
{
    protected string $table = 'departments';
    protected array $fillable = ['name', 'code', 'parent_department_id', 'manager_employee_id'];
}
