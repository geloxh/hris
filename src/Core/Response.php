<?php

    namespace App\Core;

    class Response {
        public static function json($data, int $status = 200, array $meta = []): never {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');

            $payload = ['success' => $status >= 200 && $status < 300, 'data' => $data];
            if ($meta) {
                $payload['meta'] = $meta;
            }

            echo json_encode($payload, JSON_UNESCAPED_SLASHES);
            exit;
        }

        public static function error(string $message, int $status = 400, array $errors = []): never {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');

            $payload = ['success' => false, 'message' => $message];
            if ($errors) {
                $payload['errors'] = $errors;
            }

            echo json_encode($payload, JSON_UNESCAPED_SLASHES);
            exit;
        }

        public static function noContent(): never {
            http_response_code(204);
            exit;
        }
    }
