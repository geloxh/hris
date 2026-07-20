<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class AuthController extends Controller
{
    private UserModel $users;
    private Auth $auth;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->auth = new Auth();
    }

    /** POST /api/auth/login */
    public function login(Request $request): void
    {
        $data = Validator::validate($request->body, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = $this->users->findByEmail($data['email']);

        if (!$user || !$this->auth->verifyPassword($data['password'], $user['password_hash'])) {
            Response::error('Invalid credentials.', 401);
        }

        if ($user['status'] !== 'active') {
            Response::error('This account is inactive. Contact your HR administrator.', 403);
        }

        $token = $this->auth->issueToken((int) $user['id'], $request->header('user-agent', 'api'));
        $this->users->touchLastLogin((int) $user['id']);

        $withRole = $this->users->withRole((int) $user['id']);

        Response::json([
            'token' => $token,
            'user' => [
                'id' => $withRole['id'],
                'email' => $withRole['email'],
                'role' => $withRole['role'],
                'employee_id' => $withRole['employee_id'],
            ],
        ]);
    }

    /** POST /api/auth/logout - requires AuthMiddleware */
    public function logout(Request $request): void
    {
        $token = $request->bearerToken();
        if ($token) {
            $this->auth->revokeToken($token);
        }
        Response::json(['message' => 'Logged out.']);
    }

    /** GET /api/auth/me - requires AuthMiddleware */
    public function me(Request $request): void
    {
        Response::json($request->user);
    }

    /** POST /api/auth/change-password - requires AuthMiddleware */
    public function changePassword(Request $request): void
    {
        $data = Validator::validate($request->body, [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $user = $this->users->find((int) $request->user['id']);
        if (!$user || !$this->auth->verifyPassword($data['current_password'], $user['password_hash'])) {
            Response::error('Current password is incorrect.', 422);
        }

        $this->users->update((int) $user['id'], [
            'password_hash' => $this->auth->hashPassword($data['new_password']),
        ]);

        // invalidate every other active session for security
        $this->auth->revokeAllTokensForUser((int) $user['id']);
        $newToken = $this->auth->issueToken((int) $user['id']);

        Response::json(['message' => 'Password updated.', 'token' => $newToken]);
    }
}
