<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer;

final class CustomerController extends Controller
{
    public function index(Request $request): never
    {
        $result = Customer::query()->where('branch_id', $this->branchId())->orderBy('name')->paginate(per_page(), page_number());
        $this->view('customers.index', [
            'title' => 'Customers',
            'pageTitle' => 'Customers',
            'customers' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('customers.form', [
            'title' => 'New customer',
            'pageTitle' => 'New customer',
            'customer' => null,
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'name' => 'required',
            'type' => 'required|in:retail,wholesale',
        ]);

        Customer::create([
            'branch_id' => auth()->branchId(),
            'type' => $data['type'],
            'name' => $data['name'],
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'nhis_number' => $request->input('nhis_number'),
            'credit_limit' => $request->input('credit_limit', 0),
            'credit_balance' => 0,
            'pricing_tier' => $request->input('pricing_tier'),
            'sms_opt_in' => $request->input('sms_opt_in') ? 1 : 0,
            'is_active' => 1,
        ]);

        $this->backWithSuccess('Customer saved.', '/customers');
    }

    public function show(Request $request, int $id): never
    {
        $this->edit($request, $id);
    }

    public function edit(Request $request, int $id): never
    {
        $customer = Customer::findOrFail($id);
        $this->assertSameBranch($customer);
        $this->view('customers.form', [
            'title' => 'Edit customer',
            'pageTitle' => 'Edit customer',
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $customer = Customer::findOrFail($id);
        $this->assertSameBranch($customer);
        $data = $this->validate($request->all(), ['name' => 'required']);
        $customer->update([
            'name' => $data['name'],
            'type' => $request->input('type', $customer->type),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'nhis_number' => $request->input('nhis_number'),
            'credit_limit' => $request->input('credit_limit', $customer->credit_limit),
            'pricing_tier' => $request->input('pricing_tier'),
            'sms_opt_in' => $request->input('sms_opt_in') ? 1 : 0,
            'is_active' => $request->input('is_active') ? 1 : 0,
        ]);
        $this->backWithSuccess('Customer updated.', '/customers');
    }

    public function destroy(Request $request, int $id): never
    {
        $customer = Customer::findOrFail($id);
        $this->assertSameBranch($customer);
        $customer->delete();
        $this->backWithSuccess('Customer archived.', '/customers');
    }
}
