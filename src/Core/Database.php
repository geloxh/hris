<?php

    namespace App\Core;

    use PDO;
    use PDOException;
    use PDOStatement;

    class Database {
        private static ?Database $instance = null;
        private PDO $pdo;

        private function __construct() {
            
            $cfg = require __DIR__ . '/../Config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['driver'], $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']
            );

            try {
                $this->pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
            } catch (PDOException $e) {
                logger('Database connection failed: ' . $e->getMessage(), 'ERROR');
                throw new PDOException('Could not connect to the database.');
            }
        }

        public static function getInstance(): Database {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function pdo(): PDO { 
            return $this->pdo; 
        }

        public function query(string $sql, array $params = []): PDOStatement {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }

        public function select(string $sql, array $params = []): array {
            return $this->query($sql, $params)->fetchAll();
        }

        public function selectOne(string $sql, array $params = []): ?array {
            $row = $this->query($sql, $params)->fetch();
            return $row === false ? null : $row;
        }

        public function insert(string $sql, array $params = []): string {
            $this->query($sql, $params);
            return $this->pdo->lastInsertId();
        }

        public function execute(string $sql, array $params = []): int {
            return $this->query($sql, $params)->rowCount();
        }

    public function beginTransaction(): bool { 
        return $this->pdo->beginTransaction(); 
    }

    public function commit(): bool { 
        return $this->pdo->commit(); 
    }
    
    public function rollBack(): bool {
        return $this->pdo->inTransaction() ? $this->pdo->rollBack() : false;
    }

    public function transaction(callable $callback) {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    private function __clone() {}
}
