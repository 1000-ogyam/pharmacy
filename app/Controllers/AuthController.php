<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use Throwable;

final class AuthController extends Controller
{
    public function showLogin(Request $request): never
    {
        $this->view('auth.login', [
            'title' => 'Sign in',
            'pageTitle' => 'Sign in',
            'dbWarning' => $this->databaseWarning(),
        ], 'auth');
    }

    public function login(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $ok = auth()->attempt((string) $data['email'], (string) $data['password']);
        } catch (Throwable $e) {
            error_log($e->getMessage() . "\n" . $e->getTraceAsString());
            $this->backWithError(
                database_setup_message($e) ?? ('Sign-in failed: ' . $e->getMessage()),
                '/login'
            );
        }

        if (!$ok) {
            $this->backWithError('Invalid credentials or account locked. Please try again.', '/login');
        }

        $this->redirect('/dashboard');
    }

    public function logout(Request $request): never
    {
        auth()->logout();
        $this->redirect('/login');
    }

    public function logoutRedirect(Request $request): never
    {
        $this->redirect(auth()->check() ? '/dashboard' : '/login');
    }

    private function databaseWarning(): ?string
    {
        try {
            Database::instance()->fetch('SELECT 1 AS ok');
            return null;
        } catch (Throwable $e) {
            return database_setup_message($e) ?? $e->getMessage();
        }
    }
}
