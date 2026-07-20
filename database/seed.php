<?php

/**
 * Usage (inside the php container):
 *   docker compose exec php php database/seed.php
 *
 * Idempotent: safe to run multiple times, existing rows are left alone.
 * Seeds: roles, a default admin account, starter departments, and leave types.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;

$db = Database::getInstance();
$auth = new Auth();

function seedIfMissing(Database $db, string $table, string $uniqueColumn, string $uniqueValue, array $data): void
{
    $exists = $db->selectOne("SELECT id FROM {$table} WHERE {$uniqueColumn} = :v", ['v' => $uniqueValue]);
    if ($exists) {
        echo "  - {$table}.{$uniqueColumn}={$uniqueValue} already exists, skipping\n";
        return;
    }
    $columns = array_keys($data);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', array_map(fn($c) => ":$c", $columns))
    );
    $db->insert($sql, $data);
    echo "  + {$table}.{$uniqueColumn}={$uniqueValue} created\n";
}

echo "Seeding roles...\n";
foreach ([
    ['name' => 'admin', 'description' => 'Full system access'],
    ['name' => 'hr', 'description' => 'HR staff - manages employees, leave, payroll'],
    ['name' => 'manager', 'description' => 'Approves leave and views their team'],
    ['name' => 'employee', 'description' => 'Self-service portal access only'],
] as $role) {
    seedIfMissing($db, 'roles', 'name', $role['name'], $role);
}

echo "Seeding departments...\n";
foreach (['Human Resources', 'Engineering', 'Sales', 'Finance', 'Operations'] as $i => $name) {
    seedIfMissing($db, 'departments', 'name', $name, ['name' => $name, 'code' => strtoupper(substr($name, 0, 3))]);
}

echo "Seeding leave types...\n";
foreach ([
    ['name' => 'Vacation Leave', 'default_days_per_year' => 15, 'is_paid' => 1],
    ['name' => 'Sick Leave', 'default_days_per_year' => 10, 'is_paid' => 1],
    ['name' => 'Emergency Leave', 'default_days_per_year' => 5, 'is_paid' => 1],
    ['name' => 'Unpaid Leave', 'default_days_per_year' => 0, 'is_paid' => 0],
] as $type) {
    seedIfMissing($db, 'leave_types', 'name', $type['name'], $type);
}

echo "Seeding default admin account...\n";
$adminRole = $db->selectOne("SELECT id FROM roles WHERE name = 'admin'");
$adminEmail = 'admin@hris.local';
$adminPassword = 'ChangeMe123!'; // CHANGE THIS after first login - see README
seedIfMissing($db, 'users', 'email', $adminEmail, [
    'employee_id' => null,
    'role_id' => $adminRole['id'],
    'email' => $adminEmail,
    'password_hash' => $auth->hashPassword($adminPassword),
    'status' => 'active',
]);

echo "\nDone.\n";
echo "Default admin login -> email: {$adminEmail}  password: {$adminPassword}\n";
echo "CHANGE THIS PASSWORD IMMEDIATELY via POST /api/auth/change-password after first login.\n";
