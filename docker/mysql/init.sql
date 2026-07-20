-- Runs once, only on first container start (empty data volume), via
-- docker-entrypoint-initdb.d. MYSQL_DATABASE / MYSQL_USER / MYSQL_PASSWORD from
-- docker-compose.yml already create the database and app user, so this file just
-- pins the connection defaults. Actual tables come from database/migrate.php,
-- which is deliberately run as an app-level step (not baked into the image) so
-- migrations stay visible in version control and re-runnable against any environment.

SET NAMES utf8mb4;
ALTER DATABASE `hris` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
