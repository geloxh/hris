<?php

    namespace App\Core;

    class Auth {
        private Database $db;

        public function __construct() {
            $this->db = Database::getInstance();
        }

        public function hashPassword(string $plain): string {
            return password_hash($plain, PASSWORD_BCRYPT);
        }

        public function verifyPassword(string $plain, string $hash): bool {
            return password_verify($plain, $hash);
        }

        public function issueToken(int $userId, string $deviceName = 'api'): string {
            $plain = bin2hex(random_bytes(40));
            $hash = hash('sha256', $plain);
            $ttlHours = (int) config('auth.token_ttl_hours', 12);
            $expiresAt = date('Y-m-d H:i:s', time() + $ttlHours * 3600);

            $this->db->insert(
                'INSERT INTO personal_access_tokens (user_id, token_hash, device_name, expires_at, created_at)
                VALUES (:user_id, :token_hash, :device_name, :expires_at, NOW())',
                ['user_id' => $userId, 'token_hash' => $hash, 'device_name' => $deviceName, 'expires_at' => $expiresAt]
            );

            return $plain;
        }

        public function userFromToken(string $plainToken): ?array {
            $hash = hash('sha256', $plainToken);

            $row = $this->db->selectOne(
                'SELECT pat.id as token_id, pat.expires_at, u.id, u.email, u.status, u.employee_id,
                        r.name as role, e.first_name, e.last_name
                FROM personal_access_tokens pat
                JOIN users u ON u.id = pat.user_id
                JOIN roles r ON r.id = u.role_id
                LEFT JOIN employees e ON e.id = u.employee_id
                WHERE pat.token_hash = :hash',
                ['hash' => $hash]
            );

            if (!$row || strtotime($row['expires_at']) < time() || $row['status'] !== 'active') {
                return null;
            }

            $this->db->execute(
                'UPDATE personal_access_tokens SET last_used_at = NOW() WHERE id = :id',
                ['id' => $row['token_id']]
            );

            unset($row['token_id'], $row['expires_at']);
            return $row;
        }

        public function revokeToken(string $plainToken): void {
            $this->db->execute(
                'DELETE FROM personal_access_tokens WHERE token_hash = :hash',
                ['hash' => hash('sha256', $plainToken)]
            );
        }

        public function revokeAllTokensForUser(int $userId): void {
            $this->db->execute('DELETE FROM personal_access_tokens WHERE user_id = :id', ['id' => $userId]);
        }
    }
