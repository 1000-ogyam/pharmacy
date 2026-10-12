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
use App\Services\StockService;
use PDOException;
use RuntimeException;

final class ProductController extends Controller
{
    public function index(Request $request): never
    {
        $page = (int) $request->query('page', 1);
        $q = trim((string) $request->query('q', ''));
        $listed = $this->paginateProducts($page, $q);

        $this->view('products.index', [
            'title' => 'Products',
            'pageTitle' => 'Products',
            'products' => $listed['products'],
            'priceMap' => $listed['priceMap'],
            'stockMap' => $listed['stockMap'],
            'page' => $listed['page'],
            'pages' => $listed['pages'],
            'q' => $q,
        ]);
    }

    public function stockForm(Request $request, int $id): never
    {
        $product = Product::findOrFail($id);
        $branchId = $this->branchId();
        $quantity = (new StockService())->levelQuantity((int) $product->id, $branchId);

        $this->view('products.stock', [
            'title' => 'Adjust stock',
            'pageTitle' => 'Adjust stock',
            'product' => $product,
            'quantity' => $quantity,
        ]);
    }

    public function stockUpdate(Request $request, int $id): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot adjust stock.');
        }

        $product = Product::findOrFail($id);
        $data = $this->validate($request->all(), [
            'quantity' => 'required|numeric',
        ]);

        $target = max(0, (float) $data['quantity']);

        try {
            (new StockService())->setBranchQuantity(
                (int) $product->id,
                $this->branchId(),
                $target,
                (string) $product->sku,
            );
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/products');
        }

        $this->backWithSuccess('Stock updated for ' . $product->name . '.', '/products');
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
        if (!auth()->hasRole('admin')) {
            abort(403, 'Only administrators can delete products.');
        }

        $product = Product::findOrFail($id);

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

    /** @return array{products: list<Product>, priceMap: array<int, array{retail?: float, wholesale_tier1?: float}>, stockMap: array<int, float>, page: int, pages: int} */
    private function paginateProducts(int $page, string $q): array
    {
        $branchId = $this->branchId();
        $sql = 'SELECT p.*, COALESCE(sl.quantity, 0) AS stock_quantity
                FROM products p
                LEFT JOIN stock_levels sl ON sl.product_id = p.id AND sl.branch_id = :branch_id AND sl.deleted_at IS NULL
                WHERE p.deleted_at IS NULL';
        $params = [':branch_id' => $branchId];

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(
                ['p.name', 'p.sku', 'p.barcode', 'p.generic_name'],
                $q,
                'prod_q',
            );
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY p.name ASC';

        $result = Database::instance()->paginate($sql, $params, $page);
        $products = [];
        $stockMap = [];
        $productIds = [];

        foreach ($result['data'] as $row) {
            $productId = (int) $row['id'];
            $stockMap[$productId] = (float) ($row['stock_quantity'] ?? 0);
            unset($row['stock_quantity']);
            $products[] = new Product($row);
            $productIds[] = $productId;
        }

        return [
            'products' => $products,
            'priceMap' => $this->priceMapForProducts($productIds),
            'stockMap' => $stockMap,
            'page' => $result['page'],
            'pages' => $result['pages'],
        ];
    }

    private function priceForProduct(int $productId, string $priceType): ?float
    {
        $map = $this->priceMapForProducts([$productId]);

        return $map[$productId][$priceType] ?? null;
    }

    /**
     * @param list<int> $productIds
     * @return array<int, array{retail: ?float, wholesale_tier1: ?float}>
     */
    private function priceMapForProducts(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds), static fn(int $id): bool => $id > 0));
        if ($productIds === []) {
            return [];
        }

        $branchId = (int) (auth()->branchId() ?? 0);
        $in = implode(',', $productIds);

        $rows = Database::instance()->fetchAll(
            'SELECT pp.product_id, pp.price_type, pp.price, pp.branch_id
             FROM product_prices pp
             INNER JOIN product_units pu ON pu.id = pp.unit_id AND pu.deleted_at IS NULL AND pu.is_base = 1
             WHERE pp.product_id IN (' . $in . ')
               AND pp.deleted_at IS NULL
               AND pp.price_type IN (\'retail\', \'wholesale_tier1\')
               AND (pp.branch_id IS NULL' . ($branchId > 0 ? ' OR pp.branch_id = :branch_id' : '') . ')
             ORDER BY pp.product_id ASC, pp.price_type ASC, pp.branch_id DESC',
            $branchId > 0 ? [':branch_id' => $branchId] : [],
        );

        $map = [];
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            $type = (string) $row['price_type'];
            if (!isset($map[$productId][$type])) {
                $map[$productId][$type] = (float) $row['price'];
            }
        }

        return $map;
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
