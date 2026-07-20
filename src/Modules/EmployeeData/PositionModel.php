<?php

namespace App\Modules\EmployeeData;

use App\Core\Model;

class PositionModel extends Model
{
    protected string $table = 'positions';
    protected array $fillable = ['title', 'department_id'];
}
