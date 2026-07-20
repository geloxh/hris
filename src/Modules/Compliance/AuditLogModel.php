<?php

    namespace App\Modules\Compliance;

    use App\Core\Model;

    class AuditLogModel extends Model {
        protected string $table = 'audit_logs';

        public function search(array $filters, int $page = 1, int $perPage = 50): array {
            $where = ['1=1'];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'al.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['entity_type'])) {
                $where[] = 'al.entity_type = :entity_type';
                $params['entity_type'] = $filters['entity_type'];
            }
            if (!empty($filters['entity_id'])) {
                $where[] = 'al.entity_id = :entity_id';
                $params['entity_id']   = $filters['entity_id'];
            }
            if (!empty($filters['action'])) {
                $where[] = 'al.action = :action';
                $params['action'] = $filters['action'];
            }
            if (!empty($filters['from'])) {
                $where[] = 'al.created_at >= :from';
                $params['from'] = $filters['from'];
            }
            if (!empty($filters['to'])) {
                $where[] = 'al.created_at <= :to';
                $params['to'] = $filters['to'];
            }

            $whereSql = 'WHERE ' . implode(' AND ', $where);
            $perPage = max(1, min(200, $perPage));
            $offset = (max(1, $page) - 1) * $perPage;

            $total = (int) $this->db->selectOne(
                "SELECT COUNT(*) AS total FROM audit_logs al {$whereSql}", $params
            )['total'];

            $rows = $this->db->select(
                "SELECT al.*, u.email AS user_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                {$whereSql}
                ORDER BY al.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}",
                $params
            );

            return [
                'data' => $rows,
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / $perPage) ?: 1,
                ],
            ];
        }
    }
