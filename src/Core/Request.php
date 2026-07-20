<?php

    namespace App\Core;

    class Request {
        public string $method;
        public string $path;
        public array $query;
        public array $body;
        public array $headers;
        public array $params = []; // filled in by Router from route placeholders

        /** authenticated user attached by AuthMiddleware, if any */
        public ?array $user = null;

        public function __construct() {
            $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            $this->path = $this->resolvePath();
            $this->query = $_GET ?? [];
            $this->headers = $this->resolveHeaders();
            $this->body = $this->resolveBody();
        }

        private function resolvePath(): string {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH) ?: '/';
            // strip a trailing slash (except root) so "/employees/" === "/employees"
            if (strlen($path) > 1 && str_ends_with($path, '/')) {
                $path = rtrim($path, '/');
            }
            return $path;
        }

        private function resolveHeaders(): array {
            if (function_exists('getallheaders')) {
                $headers = getallheaders() ?: [];
            } else {
                $headers = [];
                foreach ($_SERVER as $key => $value) {
                    if (str_starts_with($key, 'HTTP_')) {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                        $headers[$name] = $value;
                    }
                }
            }
            // normalize keys to lowercase for reliable lookup
            $normalized = [];
            foreach ($headers as $k => $v) {
                $normalized[strtolower($k)] = $v;
            }
            return $normalized;
        }

        private function resolveBody(): array {
            if (in_array($this->method, ['GET', 'HEAD'], true)) {
                return [];
            }
            $raw = file_get_contents('php://input');
            if (!$raw) {
                return $_POST ?? [];
            }
            $contentType = $this->headers['content-type'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [];
            }
            return $_POST ?? [];
        }

        public function input(string $key, $default = null) {
            return $this->body[$key] ?? $this->query[$key] ?? $default;
        }

        public function only(array $keys): array {
            return array_intersect_key($this->body, array_flip($keys));
        }

        public function param(string $key, $default = null) {
            return $this->params[$key] ?? $default;
        }

        public function header(string $key, $default = null) {
            return $this->headers[strtolower($key)] ?? $default;
        }

        public function bearerToken(): ?string {
            $auth = $this->header('authorization', '');
            if ($auth && preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
                return $m[1];
            }
            return null;
        }
    }
