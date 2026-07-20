<?php

    /**
     * Usage (inside the php container):
     *   docker compose exec php php database/migrate.php
     *
     * Applies every .sql file in database/migrations/ that hasn't been applied yet,
     * in filename order (hence the 001_, 002_... prefixes). Tracks progress in a
     * migrations table so this is safe to run repeatedly (e.g. on every deploy).
     */

    declare(strict_types=1);

    require dirname(__DIR__) . '/bootstrap.php';

    use App\Core\Database;

    $db = Database::getInstance();

    $db->execute('
        CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    $applied = array_column($db->select('SELECT migration FROM migrations'), 'migration');

    $dir = __DIR__ . '/migrations';
    $files = glob($dir . '/*.sql');
    sort($files); // relies on numeric filename prefixes for ordering

    $ran = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }

        echo "Applying {$name}... ";
        $sql = file_get_contents($file);

        try {
            $db->pdo()->exec($sql);
            $db->insert('INSERT INTO migrations (migration) VALUES (:migration)', ['migration' => $name]);
            echo "OK\n";
            $ran++;
        } catch (\Throwable $e) {
            echo "FAILED\n";
            fwrite(STDERR, "  {$e->getMessage()}\n");
            exit(1);
        }
    }

    echo $ran === 0 ? "Nothing to migrate - already up to date.\n" : "Done. {$ran} migration(s) applied.\n";
