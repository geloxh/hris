<?php

    namespace App\Core;

    abstract class Controller {
        protected function validated(Request $request, array $rules): array {
            return Validator::validate($request->body, $rules);
        }

        protected function currentUser(Request $request): ?array {
            return $request->user;
        }

        protected function requireRole(Request $request, array $roles): void {
            $userRole = $request->user['role'] ?? null;
            if (!$userRole || !in_array($userRole, $roles, true)) {
                Response::error('Forbidden - insufficient role.', 403);
            }
        }
    }
