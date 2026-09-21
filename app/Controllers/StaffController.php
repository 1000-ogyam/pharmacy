<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;

final class StaffController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug, b.name AS branch_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             JOIN branches b ON b.id = u.branch_id
             WHERE u.deleted_at IS NULL
             ORDER BY u.name'
        );

        $this->view('staff.index', [
            'title' => 'Staff',
            'pageTitle' => 'Staff & licences',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('staff.form', [
            'title' => 'Add staff',
            'pageTitle' => 'Add staff',
            'staff' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'role_id' => 'required|integer',
            'branch_id' => 'required|integer',
        ]);

        if ($this->emailTaken((string) $data['email'])) {
            $this->backWithError('That email is already in use.', '/staff');
        }

        User::create($this->staffPayload($request, $data, true));

        $this->backWithSuccess('Staff member created.', '/staff');
    }

    public function edit(Request $request, int $id): never
    {
        $staff = $this->findStaff($id);
        $this->view('staff.form', [
            'title' => 'Edit staff',
            'pageTitle' => 'Edit staff',
            'staff' => $staff,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $staff = $this->findStaff($id);
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'role_id' => 'required|integer',
            'branch_id' => 'required|integer',
        ];
        $password = trim((string) $request->input('password', ''));
        if ($password !== '') {
            $rules['password'] = 'min:8';
        }

        $data = $this->validate($request->all(), $rules);

        if ($this->emailTaken((string) $data['email'], $id)) {
            $this->backWithError('That email is already in use.', '/staff');
        }

        $payload = $this->staffPayload($request, $data, false);
        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($request->input('clear_login_lock')) {
            $payload['failed_login_count'] = 0;
            $payload['locked_until'] = null;
        }

        $staff->update($payload);
        $this->backWithSuccess('Staff member updated.', '/staff');
    }

    public function destroy(Request $request, int $id): never
    {
        if ((int) auth()->id() === $id) {
            $this->backWithError('You cannot remove your own account while signed in.', '/staff');
        }

        $staff = $this->findStaff($id);
        $staff->delete();
        $this->backWithSuccess('Staff member archived.', '/staff');
    }

    private function findStaff(int $id): User
    {
        $staff = User::find($id);
        if (!$staff instanceof User) {
            abort(404, 'Staff member not found.');
        }

        return $staff;
    }

    /** @return array{roles: list<Role>, branches: list<array<string, mixed>>, customers: list<Customer>, suppliers: list<Supplier>} */
    private function formOptions(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(),
            'branches' => Database::instance()->fetchAll(
                'SELECT id, name FROM branches WHERE deleted_at IS NULL ORDER BY name'
            ),
            'customers' => Customer::query()->where('is_active', 1)->orderBy('name')->limit(500)->get(),
            'suppliers' => Supplier::query()->where('is_active', 1)->orderBy('name')->limit(500)->get(),
        ];
    }

    /** @param array<string, mixed> $data */
    private function staffPayload(Request $request, array $data, bool $creating): array
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $request->input('phone') ?: null,
            'role_id' => (int) $data['role_id'],
            'branch_id' => (int) $data['branch_id'],
            'licence_number' => $request->input('licence_number') ?: null,
            'licence_expires_at' => $request->input('licence_expires_at') ?: null,
            'is_active' => $request->input('is_active') ? 1 : 0,
            'customer_id' => $this->nullableInt($request->input('customer_id')),
            'supplier_id' => $this->nullableInt($request->input('supplier_id')),
        ];

        if ($creating) {
            $payload['password'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            $payload['failed_login_count'] = 0;
            $payload['locked_until'] = null;
        }

        return $payload;
    }

    private function emailTaken(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email AND deleted_at IS NULL';
        $params = [':email' => $email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }

        return Database::instance()->fetch($sql, $params) !== null;
    }

    private function nullableInt(mixed $value): ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }
}
