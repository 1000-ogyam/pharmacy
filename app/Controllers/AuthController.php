<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class AuthController extends Controller
{
    public function showLogin(Request $request): never
    {
        $this->view('auth.login', [
            'title' => 'Sign in',
            'pageTitle' => 'Sign in',
        ], 'auth');
    }

    public function login(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt((string) $data['email'], (string) $data['password'])) {
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
}
