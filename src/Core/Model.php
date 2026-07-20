<?php

namespace App\Core;

abstract class Model
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected bool $softDeletes = false;
    /** set true on models where writes should be logged to audit_logs */
    protected bool $auditable = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        if ($this->softDeletes) $sql .= " AND deleted_at IS NULL";
        return $this->db->selectOne($sql, ['id' => $id]);
    }

    public function all(array $orderBy = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($this->softDeletes) $sql .= " WHERE deleted_at IS NULL";
        if ($orderBy) $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        return $this->db->select($sql);
    }

    public function paginate(array $conditions = [], int $page = 1, int $perPage = 20, array $orderBy = []): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($conditions);

        $total = (int) $this->db->selectOne(
            "SELECT COUNT(*) as total FROM {$this->table} {$where}", $params
        )['total'];

        $sql = "SELECT * FROM {$this->table} {$where}";
        if ($orderBy) $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->db->select($sql, $params),
            'meta' => [
                'page'      => $page,
                'per_page'  => $perPage,
                'total'     => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    public function where(array $conditions, array $orderBy = []): array
    {
        [$where, $params] = $this->buildWhere($conditions);
        $sql = "SELECT * FROM {$this->table} {$where}";
        if ($orderBy) $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        return $this->db->select($sql, $params);
    }

    public function whereOne(array $conditions): ?array
    {
        return $this->where($conditions)[0] ?? null;
    }

    public function create(array $data): array
    {
        $data    = $this->filterFillable($data);
        $columns = array_keys($data);
        $sql     = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', array_map(fn($c) => ":$c", $columns))
        );
        $id = $this->db->insert($sql, $data);
        $record = $this->find((int) $id);
        $this->audit('create', (int) $id, null, $record);
        return $record;
    }

    public function update(int $id, array $data): ?array
    {
        $data = $this->filterFillable($data);
        if (empty($data)) return $this->find($id);

        $before = $this->find($id);
        $set    = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $id;
        $this->db->execute("UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = :id", $data);
        $after = $this->find($id);
        $this->audit('update', $id, $before, $after);
        return $after;
    }

    public function delete(int $id): bool
    {
        $before = $this->find($id);
        $sql    = $this->softDeletes
            ? "UPDATE {$this->table} SET deleted_at = NOW() WHERE {$this->primaryKey} = :id"
            : "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $result = $this->db->execute($sql, ['id' => $id]) > 0;
        if ($result) $this->audit('delete', $id, $before, null);
        return $result;
    }

    protected function filterFillable(array $data): array
    {
        return empty($this->fillable) ? $data : array_intersect_key($data, array_flip($this->fillable));
    }

    protected function buildWhere(array $conditions): array
    {
        $clauses = [];
        $params  = [];

        if ($this->softDeletes) $clauses[] = 'deleted_at IS NULL';

        foreach ($conditions as $column => $value) {
            $param          = str_replace('.', '_', $column);
            $clauses[]      = "$column = :$param";
            $params[$param] = $value;
        }

        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }

    /**
     * Writes one row to audit_logs. Silently skips if $auditable = false
     * or if the audit_logs table isn't available yet (e.g. during migrations).
     */
    private function audit(string $action, int $entityId, ?array $before, ?array $after): void
    {
        if (!$this->auditable) return;

        // resolve the currently authenticated user from the session/token context
        $userId = $GLOBALS['_audit_user_id'] ?? null;

        try {
            $this->db->insert(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip)',
                [
                    'user_id'     => $userId,
                    'action'      => $action,
                    'entity_type' => $this->table,
                    'entity_id'   => $entityId,
                    'details'     => json_encode(array_filter([
                        'before' => $before,
                        'after'  => $after,
                    ])),
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (\Throwable) {
            // never let audit failure break the main operation
        }
    }
}
