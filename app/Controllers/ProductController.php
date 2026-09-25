<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\StockLevel;
use PDOException;

final class ProductController extends Controller
{
    public function index(Request $request): never
    {
        $page = (int) $request->query('page', 1);
        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(
                ['name', 'sku', 'barcode', 'generic_name'],
                $q,
                'prod_q',
            );
            $result = Database::instance()->paginate(
                'SELECT * FROM products WHERE deleted_at IS NULL AND ' . $likeSql . ' ORDER BY name',
                $likeParams,
                $page,
            );
            $result['data'] = array_map(static fn(array $row): Product => new Product($row), $result['data']);
        } else {
            $result = Product::query()->orderBy('name')->paginate(per_page(), $page);
        }

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
            'retailPrice' => $this->priceForProduct($id, 'retail'),
            'wholesalePrice' => $this->priceForProduct($id, 'wholesale_tier1'),
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $product = Product::findOrFail($id);
        $data = $this->validate($request->all(), [
            'sku' => 'required',
            'name' => 'required',
            'retail_price' => 'required|numeric',
        ]);

        $body = $request->all();

        try {
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
                'is_active' => array_key_exists('is_active', $body)
                    ? ($request->input('is_active') ? 1 : 0)
                    : (int) $product->is_active,
                'description' => $request->input('description'),
            ]);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                $this->backWithError('That SKU is already in use by another product.', '/products');
            }
            throw $e;
        }

        if ($request->input('retail_price') !== null && $request->input('retail_price') !== '') {
            $this->syncPrice($id, 'retail', (float) $request->input('retail_price'));
        }

        $wholesale = $request->input('wholesale_price');
        if ($wholesale !== null && $wholesale !== '') {
            $this->syncPrice($id, 'wholesale_tier1', (float) $wholesale);
        }

        $this->backWithSuccess('Product updated.', '/products');
    }

    public function destroy(Request $request, int $id): never
    {
        $product = Product::findOrFail($id);

        if (auth()->hasRole('admin')) {
            try {
                $product->forceDelete();
            } catch (PDOException $e) {
                if ($this->isForeignKeyViolation($e)) {
                    $this->backWithError(
                        'This product cannot be permanently deleted because other records still reference it.',
                        '/products',
                    );
                }
                throw $e;
            }

            $this->backWithSuccess('Product permanently deleted.', '/products');
        }

        $product->delete();
        $this->backWithSuccess('Product archived.', '/products');
    }

    private function priceForProduct(int $productId, string $priceType): ?float
    {
        $row = Database::instance()->fetch(
            'SELECT pp.price
             FROM product_prices pp
             INNER JOIN product_units pu ON pu.id = pp.unit_id AND pu.deleted_at IS NULL AND pu.is_base = 1
             WHERE pp.product_id = :pid AND pp.price_type = :type AND pp.deleted_at IS NULL
             LIMIT 1',
            [':pid' => $productId, ':type' => $priceType],
        );

        return $row !== null ? (float) $row['price'] : null;
    }

    private function syncPrice(int $productId, string $priceType, float $price): void
    {
        $unit = ProductUnit::query()
            ->where('product_id', $productId)
            ->where('is_base', 1)
            ->first();

        if (!$unit instanceof ProductUnit) {
            $unit = ProductUnit::create([
                'product_id' => $productId,
                'unit_name' => 'unit',
                'conversion_factor' => 1,
                'is_base' => 1,
            ]);
        }

        $existing = ProductPrice::query()
            ->where('product_id', $productId)
            ->where('unit_id', (int) $unit->id)
            ->where('price_type', $priceType)
            ->first();

        if ($existing instanceof ProductPrice) {
            $existing->update(['price' => $price]);
            return;
        }

        ProductPrice::create([
            'product_id' => $productId,
            'unit_id' => (int) $unit->id,
            'price_type' => $priceType,
            'price' => $price,
        ]);
    }

    private function isDuplicateKey(PDOException $e): bool
    {
        $code = (string) $e->getCode();
        if ($code === '23000') {
            return str_contains(strtolower($e->getMessage()), 'duplicate')
                || str_contains($e->getMessage(), '1062');
        }

        return str_contains(strtolower($e->getMessage()), 'duplicate')
            || str_contains($e->getMessage(), '1062');
    }

    private function isForeignKeyViolation(PDOException $e): bool
    {
        $code = (string) $e->getCode();
        if ($code === '23000') {
            return str_contains(strtolower($e->getMessage()), 'foreign key')
                || str_contains($e->getMessage(), '1451');
        }

        return str_contains(strtolower($e->getMessage()), 'foreign key')
            || str_contains($e->getMessage(), '1451');
    }
}
