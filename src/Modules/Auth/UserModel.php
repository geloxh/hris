<?php

    namespace App\Modules\Auth;

    use App\Core\Model;

    class UserModel extends Model {
        protected string $table = 'users';
        protected array $fillable = [
            'employee_id', 'role_id', 'email', 'password_hash', 'status',
        ];

        public function findByEmail(string $email): ?array {
            return $this->whereOne(['email' => $email]);
        }

        public function withRole(int $userId): ?array {
            return $this->db->selectOne(
                'SELECT u.id, u.email, u.status, u.employee_id, r.id as role_id, r.name as role
                FROM users u JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id',
                ['id' => $userId]
            );
        }

        public function touchLastLogin(int $userId): void {
            $this->db->execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $userId]);
        }
    }
