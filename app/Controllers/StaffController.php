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
use PDOException;

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

        $this->assertManagerMayManageRole((int) $data['role_id']);

        $duplicate = $this->duplicateMessage(
            $this->normalizeEmail((string) $data['email']),
            $this->normalizePhone($request->input('phone')),
            $this->nullableInt($request->input('customer_id')),
            $this->nullableInt($request->input('supplier_id')),
        );
        if ($duplicate !== null) {
            $this->backWithError($duplicate, '/staff');
        }

        $data['email'] = $this->normalizeEmail((string) $data['email']);

        try {
            User::create($this->staffPayload($request, $data, true));
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                $this->backWithError('That email is already in use.', '/staff');
            }
            throw $e;
        }

        $this->backWithSuccess('Staff member created.', '/staff');
    }

    public function edit(Request $request, int $id): never
    {
        $staff = $this->findStaff($id);
        $this->assertManagerMayManageUser($staff);

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
        $this->assertManagerMayManageUser($staff);

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

        $this->assertManagerMayManageRole((int) $data['role_id']);

        $duplicate = $this->duplicateMessage(
            $this->normalizeEmail((string) $data['email']),
            $this->normalizePhone($request->input('phone')),
            $this->nullableInt($request->input('customer_id')),
            $this->nullableInt($request->input('supplier_id')),
            $id,
        );
        if ($duplicate !== null) {
            $this->backWithError($duplicate, '/staff');
        }

        $data['email'] = $this->normalizeEmail((string) $data['email']);

        $payload = $this->staffPayload($request, $data, false);
        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($request->input('clear_login_lock')) {
            $payload['failed_login_count'] = 0;
            $payload['locked_until'] = null;
        }

        try {
            $staff->update($payload);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                $this->backWithError('That email is already in use.', '/staff');
            }
            throw $e;
        }

        $this->backWithSuccess('Staff member updated.', '/staff');
    }

    public function destroy(Request $request, int $id): never
    {
        if ((int) auth()->id() === $id) {
            $this->backWithError('You cannot remove your own account while signed in.', '/staff');
        }

        $staff = $this->findStaff($id);
        $this->assertManagerMayManageUser($staff);

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
        $this->ensureManagerRoleExists();

        $roles = Role::query()->orderBy('name')->get();
        if ($this->actingAsManager()) {
            $roles = array_values(array_filter(
                $roles,
                static fn (Role $role): bool => !in_array($role->slug, ['admin', 'customer', 'supplier'], true),
            ));
        }

        return [
            'roles' => $roles,
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

    private function duplicateMessage(
        string $email,
        ?string $phoneDigits,
        ?int $customerId,
        ?int $supplierId,
        ?int $exceptId = null,
    ): ?string {
        if ($this->emailTaken($email, $exceptId)) {
            return 'That email is already in use by another staff account.';
        }

        if ($this->emailArchived($email)) {
            return 'That email belongs to an archived account. Use a different email or ask an admin to restore the old account.';
        }

        if ($phoneDigits !== null && $this->phoneTaken($phoneDigits, $exceptId)) {
            return 'That phone number is already linked to another staff account.';
        }

        if ($customerId !== null && $this->portalCustomerTaken($customerId, $exceptId)) {
            return 'That customer already has a portal login linked to another staff account.';
        }

        if ($supplierId !== null && $this->portalSupplierTaken($supplierId, $exceptId)) {
            return 'That supplier already has a portal login linked to another staff account.';
        }

        return null;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (!is_string($phone) && !is_numeric($phone)) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        return $digits;
    }

    private function emailTaken(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE LOWER(TRIM(email)) = :email AND deleted_at IS NULL';
        $params = [':email' => $email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }

        return Database::instance()->fetch($sql, $params) !== null;
    }

    private function emailArchived(string $email): bool
    {
        return Database::instance()->fetch(
            'SELECT id FROM users WHERE LOWER(TRIM(email)) = :email AND deleted_at IS NOT NULL LIMIT 1',
            [':email' => $email],
        ) !== null;
    }

    private function phoneTaken(string $phoneDigits, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id, phone FROM users WHERE deleted_at IS NULL AND phone IS NOT NULL AND phone <> \'\'';
        $params = [];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }

        foreach (Database::instance()->fetchAll($sql, $params) as $row) {
            if ($this->normalizePhone($row['phone'] ?? '') === $phoneDigits) {
                return true;
            }
        }

        return false;
    }

    private function portalCustomerTaken(int $customerId, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE customer_id = :cid AND deleted_at IS NULL';
        $params = [':cid' => $customerId];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }

        return Database::instance()->fetch($sql, $params) !== null;
    }

    private function portalSupplierTaken(int $supplierId, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE supplier_id = :sid AND deleted_at IS NULL';
        $params = [':sid' => $supplierId];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }

        return Database::instance()->fetch($sql, $params) !== null;
    }

    private function ensureManagerRoleExists(): void
    {
        $db = Database::instance();
        $row = $db->fetch(
            'SELECT id, deleted_at FROM roles WHERE slug = :slug LIMIT 1',
            [':slug' => 'manager'],
        );

        if ($row === null) {
            Role::create([
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Branch manager',
            ]);

            return;
        }

        if ($row['deleted_at'] !== null) {
            $db->execute(
                'UPDATE roles SET deleted_at = NULL, name = :name, description = :description, updated_at = NOW() WHERE id = :id',
                [
                    ':name' => 'Manager',
                    ':description' => 'Branch manager',
                    ':id' => (int) $row['id'],
                ],
            );
        }
    }

    private function actingAsManager(): bool
    {
        return (auth()->user()?->role_slug ?? '') === 'manager';
    }

    private function assertManagerMayManageRole(int $roleId): void
    {
        if (!$this->actingAsManager()) {
            return;
        }

        $role = Role::find($roleId);
        if ($role instanceof Role && in_array($role->slug, ['admin', 'customer', 'supplier'], true)) {
            $this->backWithError('Managers cannot assign administrator or portal-only roles.', '/staff');
        }
    }

    private function assertManagerMayManageUser(User $staff): void
    {
        if (!$this->actingAsManager()) {
            return;
        }

        $row = Database::instance()->fetch(
            'SELECT slug FROM roles WHERE id = :id',
            [':id' => (int) $staff->role_id],
        );
        $slug = (string) ($row['slug'] ?? '');
        if (in_array($slug, ['admin', 'customer', 'supplier'], true)) {
            $this->backWithError('Managers cannot change administrator or portal-only accounts.', '/staff');
        }
    }

    private function isDuplicateKey(PDOException $e): bool
    {
        $code = (string) $e->getCode();
        if ($code === '23000') {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'duplicate');
    }

    private function nullableInt(mixed $value): ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }
}
