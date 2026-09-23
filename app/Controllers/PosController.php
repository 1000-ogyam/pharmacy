<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\PricingService;
use App\Services\SaleService;
use App\Services\StockService;
use RuntimeException;

final class PosController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $q = trim((string) $request->query('q', ''));

        $sql = 'SELECT p.*, sl.quantity AS stock
                FROM products p
                LEFT JOIN stock_levels sl ON sl.product_id = p.id AND sl.branch_id = :b AND sl.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.is_active = 1';
        $params = [':b' => $branchId];

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(
                ['p.name', 'p.sku', 'p.barcode', 'p.generic_name'],
                $q,
                'pos_q',
            );
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY p.name ASC LIMIT 40';

        $products = Database::instance()->fetchAll($sql, $params);
        $pricing = new PricingService();

        foreach ($products as &$product) {
            $product['price'] = $pricing->unitPrice((int) $product['id'], $branchId);
        }
        unset($product);

        $customers = Customer::where('is_active', 1)->where('branch_id', $branchId)->orderBy('name')->limit(200)->get();

        $this->view('pos.index', [
            'title' => 'Retail POS',
            'pageTitle' => 'Retail POS',
            'products' => $products,
            'customers' => $customers,
            'q' => $q,
        ]);
    }

    public function checkout(Request $request): never
    {
        $items = $request->input('items');
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        try {
            $sale = (new SaleService())->checkout([
                'items' => $items,
                'customer_id' => $request->input('customer_id'),
                'discount' => $request->input('discount', 0),
                'payment_method' => $request->input('payment_method', 'cash'),
                'notes' => $request->input('notes'),
                'sale_type' => 'retail',
            ]);
        } catch (RuntimeException $e) {
            if ($request->wantsJson()) {
                $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            $this->backWithError($e->getMessage(), '/pos');
        }

        if ($request->wantsJson()) {
            $this->json([
                'ok' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'receipt_url' => url('/pos/receipt/' . $sale->id),
            ]);
        }

        $this->redirect('/pos/receipt/' . $sale->id);
    }

    public function receipt(Request $request, int $id): never
    {
        $sale = Sale::findOrFail($id);
        $this->assertSameBranch($sale);
        $items = Database::instance()->fetchAll(
            'SELECT si.*, p.name AS product_name, b.batch_number
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN batches b ON b.id = si.batch_id
             WHERE si.sale_id = :id AND si.deleted_at IS NULL',
            [':id' => $id]
        );

        $customer = $sale->customer_id ? Customer::find((int) $sale->customer_id) : null;
        $cashier = User::find((int) $sale->user_id);
        $branch = Branch::find((int) $sale->branch_id);
        $payment = Database::instance()->fetch(
            'SELECT method, amount FROM payments
             WHERE payable_type = :type AND payable_id = :id AND deleted_at IS NULL
             ORDER BY id DESC LIMIT 1',
            [':type' => 'sale', ':id' => $id]
        );

        $method = (string) ($payment['method'] ?? ($sale->payment_status === 'credit' ? 'credit' : 'cash'));
        $paymentLabel = match ($method) {
            'cash' => 'Cash',
            'mobile_money' => 'Mobile money',
            'card' => 'Card',
            'credit' => 'Credit',
            default => ucfirst(str_replace('_', ' ', $method)),
        };

        $this->view('pos.receipt', [
            'title' => 'Receipt ' . $sale->sale_number,
            'pageTitle' => 'Receipt',
            'sale' => $sale,
            'items' => $items,
            'customer' => $customer,
            'cashier' => $cashier,
            'branch' => $branch,
            'paymentLabel' => $paymentLabel,
        ]);
    }

    public function search(Request $request): never
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $q = trim((string) $request->query('q', ''));
        $stock = new StockService();
        $pricing = new PricingService();

        [$likeSql, $likeParams] = sql_like_or(
            ['name', 'sku', 'barcode', 'generic_name'],
            $q,
            'pos_api',
        );

        $sql = 'SELECT id, sku, name, generic_name, barcode, strength, dosage_form FROM products
             WHERE deleted_at IS NULL AND is_active = 1';
        $params = [];

        if ($q !== '') {
            $sql .= ' AND ' . $likeSql;
            $params = $likeParams;
        }

        $sql .= ' ORDER BY name LIMIT 40';

        $rows = Database::instance()->fetchAll($sql, $params);

        foreach ($rows as &$row) {
            $row['stock'] = $stock->available((int) $row['id'], $branchId);
            $row['price'] = $pricing->unitPrice((int) $row['id'], $branchId);
        }

        $this->json(['ok' => true, 'data' => $rows]);
    }
}
