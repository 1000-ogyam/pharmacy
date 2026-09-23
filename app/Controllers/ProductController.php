<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\StockLevel;

final class ProductController extends Controller
{
    public function index(Request $request): never
    {
        $page = (int) $request->query('page', 1);
        $q = trim((string) $request->query('q', ''));
        $query = Product::query()->orderBy('name');

        if ($q !== '') {
            $query->where('name', 'LIKE', '%' . $q . '%');
        }

        $result = $query->paginate(per_page(), $page);

        $this->view('products.index', [
            'title' => 'Products',
            'pageTitle' => 'Products',
            'products' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'q' => $q,
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('products.form', [
            'title' => 'New product',
            'pageTitle' => 'New product',
            'product' => null,
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'sku' => 'required',
            'name' => 'required',
            'retail_price' => 'required|numeric',
        ]);

        $product = Product::create([
            'sku' => $data['sku'],
            'barcode' => $request->input('barcode'),
            'name' => $data['name'],
            'generic_name' => null,
            'category' => $request->input('category'),
            'dosage_form' => $request->input('dosage_form'),
            'strength' => $request->input('strength'),
            'manufacturer' => $request->input('manufacturer'),
            'requires_prescription' => $request->input('requires_prescription') ? 1 : 0,
            'is_controlled' => $request->input('is_controlled') ? 1 : 0,
            'is_active' => 1,
            'description' => $request->input('description'),
        ]);

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'unit_name' => $request->input('unit_name', 'unit'),
            'conversion_factor' => 1,
            'is_base' => 1,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'price_type' => 'retail',
            'price' => $data['retail_price'],
        ]);

        if ($request->input('wholesale_price')) {
            ProductPrice::create([
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'price_type' => 'wholesale_tier1',
                'price' => $request->input('wholesale_price'),
            ]);
        }

        $branchId = auth()->branchId();
        $quantity = max(0, (float) $request->input('quantity', 0));
        if ($branchId !== null && $quantity > 0) {
            $batchNumber = 'OPEN-' . strtoupper($data['sku']) . '-' . date('ymd');
            Batch::create([
                'product_id' => (int) $product->id,
                'branch_id' => $branchId,
                'batch_number' => $batchNumber,
                'expiry_date' => date('Y-m-d', strtotime('+24 months')),
                'manufacture_date' => date('Y-m-d'),
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'unit_cost' => 0,
            ]);
            StockLevel::create([
                'product_id' => (int) $product->id,
                'branch_id' => $branchId,
                'quantity' => $quantity,
                'reorder_level' => 10,
            ]);
        }

        $this->backWithSuccess('Product created.', '/products');
    }

    public function show(Request $request, int $id): never
    {
        $this->edit($request, $id);
    }

    public function edit(Request $request, int $id): never
    {
        $product = Product::findOrFail($id);
        $this->view('products.form', [
            'title' => 'Edit product',
            'pageTitle' => 'Edit product',
            'product' => $product,
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $product = Product::findOrFail($id);
        $data = $this->validate($request->all(), [
            'sku' => 'required',
            'name' => 'required',
        ]);

        $product->update([
            'sku' => $data['sku'],
            'barcode' => $request->input('barcode'),
            'name' => $data['name'],
            'generic_name' => $request->input('generic_name'),
            'category' => $request->input('category'),
            'dosage_form' => $request->input('dosage_form'),
            'strength' => $request->input('strength'),
            'manufacturer' => $request->input('manufacturer'),
            'requires_prescription' => $request->input('requires_prescription') ? 1 : 0,
            'is_controlled' => $request->input('is_controlled') ? 1 : 0,
            'is_active' => $request->input('is_active') ? 1 : 0,
            'description' => $request->input('description'),
        ]);

        $this->backWithSuccess('Product updated.', '/products');
    }

    public function destroy(Request $request, int $id): never
    {
        Product::findOrFail($id)->delete();
        $this->backWithSuccess('Product archived.', '/products');
    }
}
