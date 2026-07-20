<?php

    /**
     * Global helper functions.
     * Kept intentionally small - anything module-specific belongs in a Service class instead.
     */

    if (!function_exists('env')) {
        /**
         * Read a value from the loaded environment (populated by loadEnv()).
         */
        function env(string $key, $default = null) {
            $value = $_ENV[$key] ?? getenv($key);
            if ($value === false || $value === null) {
                return $default;
            }
            // normalize common boolean-ish strings
            $lower = strtolower((string)$value);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;
            return $value;
        }
    }

    if (!function_exists('loadEnv')) {
        
        /**
         * Minimal .env parser - no external dependency needed.
         */
        function loadEnv(string $path): void {
            if (!is_file($path)) {
                return;
            }
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // strip surrounding quotes
                if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'")) {
                    $value = trim($value, "\"'");
                }
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }

    if (!function_exists('config')) {
        /**
         * Dot-notation access into the merged config array (App\Config\config()).
         */
        function config(string $key, $default = null) {
            
            static $config = null;
            if ($config === null) {
                $config = require __DIR__ . '/../Config/config.php';
            }
            $segments = explode('.', $key);
            $value = $config;
            foreach ($segments as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }
                $value = $value[$segment];
            }
            return $value;
        }
    }

    if (!function_exists('base_path')) {
        function base_path(string $path = ''): string {
            return rtrim(dirname(__DIR__, 2), '/') . ($path ? '/' . ltrim($path, '/') : '');
        }
    }

    if (!function_exists('storage_path')) {
        function storage_path(string $path = ''): string {
            return base_path('storage') . ($path ? '/' . ltrim($path, '/') : '');
        }
    }

    if (!function_exists('logger')) {
        /**
         * Very small file logger. Writes to storage/logs/app.log.
         */
        function logger(string $message, string $level = 'INFO', array $context = []): void {
            $dir = storage_path('logs');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $line = sprintf(
                "[%s] %s: %s %s\n",
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                $context ? json_encode($context) : ''
            );
            file_put_contents($dir . '/app.log', $line, FILE_APPEND);
        }
    }

    if (!function_exists('str_uuid4')) {
        function str_uuid4(): string {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
    }
