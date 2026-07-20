<?php

    namespace App\Modules\Employee;

    use App\Core\Model;

    class EmployeeModel extends Model {
        protected string $table = 'employees';
        protected bool $softDeletes = true;
        protected array $fillable = [
            'employee_number', 'first_name', 'last_name', 'middle_name', 'email', 'phone',
            'birth_date', 'gender', 'hire_date', 'employment_status', 'employment_type',
            'department_id', 'position_id', 'manager_id', 'address',
            'emergency_contact_name', 'emergency_contact_phone',
        ];

        /** Joined view used for list/detail responses so the frontend doesn't need extra round trips. */
        public function withRelations(int $id): ?array {
            return $this->db->selectOne(
                'SELECT e.*, d.name AS department_name, p.title AS position_title,
                        CONCAT(m.first_name, " ", m.last_name) AS manager_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN employees m ON m.id = e.manager_id
                WHERE e.id = :id AND e.deleted_at IS NULL',
                ['id' => $id]
            );
        }

        public function searchPaginate(array $filters, int $page, int $perPage): array {
            $where = ['e.deleted_at IS NULL'];
            $params = [];

            if (!empty($filters['department_id'])) {
                $where[] = 'e.department_id = :department_id';
                $params['department_id'] = $filters['department_id'];
            }
            if (!empty($filters['employment_status'])) {
                $where[] = 'e.employment_status = :employment_status';
                $params['employment_status'] = $filters['employment_status'];
            }
            if (!empty($filters['q'])) {
                $where[] = '(e.first_name LIKE :q OR e.last_name LIKE :q OR e.employee_number LIKE :q OR e.email LIKE :q)';
                $params['q'] = '%' . $filters['q'] . '%';
            }

            $whereSql = 'WHERE ' . implode(' AND ', $where);
            $page = max(1, $page);
            $perPage = max(1, min(200, $perPage));
            $offset = ($page - 1) * $perPage;

            $total = (int) $this->db->selectOne(
                "SELECT COUNT(*) as total FROM employees e {$whereSql}",
                $params
            )['total'];

            $data = $this->db->select(
                "SELECT e.*, d.name AS department_name, p.title AS position_title
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                {$whereSql}
                ORDER BY e.last_name ASC, e.first_name ASC
                LIMIT {$perPage} OFFSET {$offset}",
                $params
            );

            return [
                'data' => $data,
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / $perPage) ?: 1,
                ],
            ];
        }

        public function nextEmployeeNumber(): string {
            $year = date('Y');
            $row = $this->db->selectOne(
                "SELECT employee_number FROM employees
                WHERE employee_number LIKE :prefix
                ORDER BY id DESC LIMIT 1",
                ['prefix' => "EMP-{$year}-%"]
            );

            $next = 1;
            if ($row) {
                $parts = explode('-', $row['employee_number']);
                $next = ((int) end($parts)) + 1;
            }

            return sprintf('EMP-%s-%04d', $year, $next);
        }
    }
