<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Supplier;

final class SupplierController extends Controller
{
    public function index(Request $request): never
    {
        $result = Supplier::query()->orderBy('name')->paginate(per_page(), page_number());
        $this->view('suppliers.index', [
            'title' => 'Suppliers',
            'pageTitle' => 'Suppliers',
            'suppliers' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('suppliers.form', ['title' => 'New supplier', 'pageTitle' => 'New supplier', 'supplier' => null]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), ['name' => 'required']);
        Supplier::create([
            'name' => $data['name'],
            'contact_person' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'currency_code' => $request->input('currency_code', 'GHS'),
            'payment_terms_days' => $request->input('payment_terms_days', 30),
            'is_active' => 1,
        ]);
        $this->backWithSuccess('Supplier saved.', '/suppliers');
    }

    public function show(Request $request, int $id): never
    {
        $this->edit($request, $id);
    }

    public function edit(Request $request, int $id): never
    {
        $this->view('suppliers.form', [
            'title' => 'Edit supplier',
            'pageTitle' => 'Edit supplier',
            'supplier' => Supplier::findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update([
            'name' => $request->input('name', $supplier->name),
            'contact_person' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'currency_code' => $request->input('currency_code', 'GHS'),
            'payment_terms_days' => $request->input('payment_terms_days', 30),
            'is_active' => $request->input('is_active') ? 1 : 0,
        ]);
        $this->backWithSuccess('Supplier updated.', '/suppliers');
    }

    public function destroy(Request $request, int $id): never
    {
        Supplier::findOrFail($id)->delete();
        $this->backWithSuccess('Supplier archived.', '/suppliers');
    }
}
