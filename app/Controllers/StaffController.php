<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Role;
use App\Models\User;

final class StaffController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT u.*, r.name AS role_name, b.name AS branch_name
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
            'roles' => Role::all(),
            'branches' => Database::instance()->fetchAll('SELECT * FROM branches WHERE deleted_at IS NULL'),
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

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $request->input('phone'),
            'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'role_id' => $data['role_id'],
            'branch_id' => $data['branch_id'],
            'licence_number' => $request->input('licence_number'),
            'licence_expires_at' => $request->input('licence_expires_at'),
            'is_active' => 1,
        ]);

        $this->backWithSuccess('Staff member created.', '/staff');
    }
}
