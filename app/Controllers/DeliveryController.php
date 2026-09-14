<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Delivery;

final class DeliveryController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT d.*, c.name AS customer_name
             FROM deliveries d
             JOIN customers c ON c.id = d.customer_id
             WHERE d.deleted_at IS NULL AND d.branch_id = :b
             ORDER BY d.id DESC',
            [':b' => $this->branchId()]
        );

        $this->view('deliveries.index', [
            'title' => 'Deliveries',
            'pageTitle' => 'Deliveries',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'customers' => Customer::query()->where('branch_id', $this->branchId())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'customer_id' => 'required|integer',
        ]);
        $customer = Customer::findOrFail((int) $data['customer_id']);
        $this->assertSameBranch($customer);

        Delivery::create([
            'branch_id' => $this->branchId(),
            'customer_id' => $customer->id,
            'driver_name' => $request->input('driver_name'),
            'status' => 'scheduled',
            'address' => $request->input('address'),
            'scheduled_at' => $request->input('scheduled_at') ?: date('Y-m-d H:i:s'),
        ]);

        $this->backWithSuccess('Delivery scheduled.', '/deliveries');
    }

    public function complete(Request $request, int $id): never
    {
        $delivery = Delivery::findOrFail($id);
        $this->assertSameBranch($delivery);
        $delivery->update([
            'status' => 'delivered',
            'delivered_at' => date('Y-m-d H:i:s'),
        ]);
        $this->backWithSuccess('Marked delivered.', '/deliveries');
    }
}
