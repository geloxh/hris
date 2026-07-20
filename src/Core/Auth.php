<?php

namespace App\Core;

/**
 * Stateless-ish bearer token auth backed by MySQL (personal_access_tokens table).
 *
 * Why not PHP sessions? The frontend is plain JS calling a JSON API, possibly from
 * a different origin/port during development, and this design keeps auth state
 * out of sticky server sessions so the PHP-FPM containers stay horizontally
 * scalable (no shared session storage required).
 *
 * Why not JWT? Opaque DB-backed tokens can be revoked instantly (delete the row) -
 * important for HR systems (terminated employee access must die immediately).
 * If throughput ever demands it, swap the lookup for a Redis cache in front of MySQL
 * without changing any calling code.
 */
class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Creates a token row for the user and returns the plaintext token
     * (only ever available at issue time - only the hash is stored).
     */
    public function issueToken(int $userId, string $deviceName = 'api'): string
    {
        $plain = bin2hex(random_bytes(40));
        $hash = hash('sha256', $plain);
        $ttlHours = (int) config('auth.token_ttl_hours', 12);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlHours * 3600);

        $this->db->insert(
            'INSERT INTO personal_access_tokens (user_id, token_hash, device_name, expires_at, created_at)
             VALUES (:user_id, :token_hash, :device_name, :expires_at, NOW())',
            [
                'user_id' => $userId,
                'token_hash' => $hash,
                'device_name' => $deviceName,
                'expires_at' => $expiresAt,
            ]
        );

        return $plain;
    }

    /**
     * Resolves a bearer token to the authenticated user, or null if invalid/expired.
     * Also touches last_used_at so idle-session/device auditing is possible.
     */
    public function userFromToken(string $plainToken): ?array
    {
        $hash = hash('sha256', $plainToken);

        $row = $this->db->selectOne(
            'SELECT pat.id as token_id, pat.expires_at, u.id, u.email, u.status, u.employee_id, r.name as role,
                    e.first_name, e.last_name
             FROM personal_access_tokens pat
             JOIN users u ON u.id = pat.user_id
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE pat.token_hash = :hash',
            ['hash' => $hash]
        );

        if (!$row) {
            return null;
        }
        if (strtotime($row['expires_at']) < time()) {
            return null;
        }
        if ($row['status'] !== 'active') {
            return null;
        }

        $this->db->execute(
            'UPDATE personal_access_tokens SET last_used_at = NOW() WHERE id = :id',
            ['id' => $row['token_id']]
        );

        unset($row['token_id'], $row['expires_at']);
        return $row;
    }

    public function revokeToken(string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $this->db->execute('DELETE FROM personal_access_tokens WHERE token_hash = :hash', ['hash' => $hash]);
    }

    public function revokeAllTokensForUser(int $userId): void
    {
        $this->db->execute('DELETE FROM personal_access_tokens WHERE user_id = :id', ['id' => $userId]);
    }
}
