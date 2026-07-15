<?php

    namespace App\Service;

    use Doctrin\DBAL\Connection;
    use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
    
    /**
     * ApiNonceStore
     *
     * Replay protection for inbound signed requests, backed by a plain
     * `api_nonces` table (see migrations/ — a versioned Doctrine migration,
     * not a Doctrine entity/ORM mapping, since this is an operational/audit
     * table with no relations, same as ARS's own copy).
     *
     * Uses raw DBAL rather than the ORM: a single insert-or-detect-duplicate
     * operation, relying on the table's unique (service_id, nonce) constraint
     * for atomicity — the same approach ARS's own NonceStore takes, so the
     * two behave identically even though one uses PDO and the other DBAL.
     */

    class ApiNonceStore {
        public function __construct(private Connection $connection) {}

        /**
         * @return bool True if this (serviceId, nonce) pair was already seen
         *              (i.e. this is a replay). Records it if not.
         */
        public function seen(string $serviceId, string $nonce, int $timestamp): bool {
            try {
                $this->connection->insert(
                    'api_nonces', [
                        'service_id' => $serviceId,
                        'nonce' => $nonce,
                        'request_ts' => date('Y-m-d H:i:s', $timestamp),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]
                );

                return false; // Insert succeeded — first time we've seen this nonce.
            } catch (UniqueConstraintViolationException) {
                return true; // Already exists — this is a replay.
            }
        }

        /**
         * Deletes nonces outside the signature validity window — they can
         * never be replayed successfully again (the timestamp check in
         * HmacSigner::verify() would reject them first), so there's no reason
         * to keep them. Call periodically (e.g. from a scheduled command),
         * not on every request.
         */
        public function pruneExpired(int $windowSeconds): void {
            $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds - 60); // small safety margin
            $this->connection->executeStatement(
                'DELETE FROM api_nonces WHERE requests_ts < :cutoff',
                ['cutoff' => $cutoff]
            );
        }
    }