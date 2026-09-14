<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?self $instance = null;
    private ?User $user = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function attempt(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user instanceof User) {
            $this->recordFailedAttempt($email);
            return false;
        }

        if ($user->locked_until && strtotime((string) $user->locked_until) > time()) {
            return false;
        }

        if (!(int) $user->is_active) {
            return false;
        }

        if (!password_verify($password, (string) $user->password)) {
            $this->bumpFailures($user);
            return false;
        }

        $user->update([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        Session::regenerate();
        Session::set('user_id', (int) $user->id);
        Session::set('branch_id', (int) $user->branch_id);
        $this->user = User::find((int) $user->id);

        return true;
    }

    public function login(User $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user->id);
        Session::set('branch_id', (int) $user->branch_id);
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->user = null;
        Session::forget('user_id');
        Session::forget('branch_id');
        Session::regenerate();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = Session::get('user_id');
        if (!$id) {
            return null;
        }

        $user = User::find((int) $id);
        if (!$user instanceof User || !(int) $user->is_active) {
            return null;
        }

        return $this->user = $user;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user ? (int) $user->id : null;
    }

    public function branchId(): ?int
    {
        $user = $this->user();
        return $user ? (int) $user->branch_id : null;
    }

    public function hasRole(string ...$roles): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $slug = (string) $user->role_slug;
        return in_array($slug, $roles, true);
    }

    public function customerId(): ?int
    {
        $id = (int) ($this->user()?->customer_id ?? 0);
        return $id > 0 ? $id : null;
    }

    public function supplierId(): ?int
    {
        $id = (int) ($this->user()?->supplier_id ?? 0);
        return $id > 0 ? $id : null;
    }

    private function bumpFailures(User $user): void
    {
        $max = (int) config('app.login_max_attempts', 5);
        $lockMinutes = (int) config('app.login_lock_minutes', 15);
        $count = (int) $user->failed_login_count + 1;

        $data = ['failed_login_count' => $count];
        if ($count >= $max) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
        }

        $user->update($data);
    }

    private function recordFailedAttempt(string $email): void
    {
        // Avoid user enumeration; no-op beyond a small delay.
        usleep(200000);
        unset($email);
    }
}
