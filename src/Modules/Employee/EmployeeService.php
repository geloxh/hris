<?php

namespace App\Modules\Employee;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\Auth\UserModel;

/**
 * Anything that touches more than one table (employee + optional login account,
 * audit trail, etc) belongs here rather than in the controller or a single Model.
 */
class EmployeeService
{
    private EmployeeModel $employees;
    private UserModel $users;
    private Auth $auth;
    private Database $db;

    public function __construct()
    {
        $this->employees = new EmployeeModel();
        $this->users = new UserModel();
        $this->auth = new Auth();
        $this->db = Database::getInstance();
    }

    /**
     * Creates the employee record and, optionally, a linked user login account
     * (self-service portal access) in a single transaction so we never end up
     * with an employee that has a half-created account.
     */
    public function onboard(array $employeeData, ?array $accountData = null): array
    {
        return $this->db->transaction(function () use ($employeeData, $accountData) {
            $employeeData['employee_number'] = $employeeData['employee_number']
                ?? $this->employees->nextEmployeeNumber();
            $employeeData['employment_status'] = $employeeData['employment_status'] ?? 'active';

            $employee = $this->employees->create($employeeData);

            if ($accountData) {
                $this->users->create([
                    'employee_id' => $employee['id'],
                    'role_id' => $accountData['role_id'],
                    'email' => $accountData['email'] ?? $employee['email'],
                    'password_hash' => $this->auth->hashPassword($accountData['password']),
                    'status' => 'active',
                ]);
            }

            return $this->employees->withRelations((int) $employee['id']);
        });
    }

    public function terminate(int $employeeId, string $lastWorkingDate): array
    {
        return $this->db->transaction(function () use ($employeeId, $lastWorkingDate) {
            $employee = $this->employees->update($employeeId, [
                'employment_status' => 'terminated',
            ]);

            $user = $this->users->whereOne(['employee_id' => $employeeId]);
            if ($user) {
                $this->users->update((int) $user['id'], ['status' => 'inactive']);
                $this->auth->revokeAllTokensForUser((int) $user['id']);
            }

            logger("Employee {$employeeId} terminated, last working date {$lastWorkingDate}", 'INFO');

            return $employee;
        });
    }
}
