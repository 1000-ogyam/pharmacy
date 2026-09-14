<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = null): never
    {
        $data['authUser'] = auth()->user();
        $data['currentPath'] = request_path();

        if (Request::capture()->isModal()) {
            $data['modal'] = true;
            Response::html(View::render($template, $data, null));
        }

        $layout ??= layout_for_role();
        Response::html(View::render($template, $data, $layout));
    }

    protected function redirect(string $path, int $code = 302): never
    {
        Response::redirect($path, $code);
    }

    protected function json(mixed $data, int $code = 200): never
    {
        Response::json($data, $code);
    }

    protected function branchId(): int
    {
        $id = (int) (auth()->branchId() ?? 0);
        if ($id <= 0) {
            abort(403, 'No branch is assigned to this account.');
        }
        return $id;
    }

    protected function assertSameBranch(object $record, string $column = 'branch_id'): void
    {
        if ((int) ($record->{$column} ?? 0) !== $this->branchId()) {
            abort(403, 'This record is not available at your branch.');
        }
    }

    protected function validate(array $data, array $rules): array
    {
        $validator = new Validator();
        $errors = $validator->validate($data, $rules);

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old', $data);
            Session::flash('error', 'Please correct the highlighted fields.');
            $this->redirect($this->refererPath());
        }

        return $data;
    }

    protected function validateOrFail(array $data, array $rules): array
    {
        $validator = new Validator();
        $errors = $validator->validate($data, $rules);

        if ($errors !== []) {
            if (Request::capture()->wantsJson()) {
                $this->json(['ok' => false, 'errors' => $errors], 422);
            }

            Session::flash('errors', $errors);
            Session::flash('_old', $data);
            Session::flash('error', 'Please correct the highlighted fields.');
            $this->redirect($this->refererPath());
        }

        return $data;
    }

    protected function backWithSuccess(string $message, ?string $to = null): never
    {
        flash('success', $message);
        $this->redirect($to ?? $this->refererPath());
    }

    protected function backWithError(string $message, ?string $to = null): never
    {
        flash('error', $message);
        $this->redirect($to ?? $this->refererPath());
    }

    protected function refererPath(): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            return '/dashboard';
        }

        $parts = parse_url($referer);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $current = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
        if ($host === '' || $current === '' || $host !== $current) {
            return '/dashboard';
        }

        $path = (string) ($parts['path'] ?? '/dashboard');
        $base = app_base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/dashboard';
        }

        return str_starts_with($path, '/') ? $path : '/dashboard';
    }
}
