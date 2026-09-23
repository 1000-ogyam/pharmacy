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
use App\Services\SaleService;
use RuntimeException;

final class SaleController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = $this->branchId();
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $q = trim((string) $request->query('q', ''));

        $sql = 'SELECT s.*, u.name AS cashier_name, c.name AS customer_name
                FROM sales s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN customers c ON c.id = s.customer_id
                WHERE s.deleted_at IS NULL AND s.branch_id = :branch_id';
        $params = [':branch_id' => $branchId];

        if ($from !== '') {
            $sql .= ' AND DATE(s.created_at) >= :from';
            $params[':from'] = $from;
        }
        if ($to !== '') {
            $sql .= ' AND DATE(s.created_at) <= :to';
            $params[':to'] = $to;
        }
        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(
                ['s.sale_number', 'c.name', 'u.name'],
                $q,
                'sale_q',
            );
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY s.created_at DESC, s.id DESC';

        $result = Database::instance()->paginate($sql, $params);

        $this->view('sales.index', [
            'title' => 'Sales history',
            'pageTitle' => 'Sales history',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'canManage' => auth()->hasRole('admin'),
        ]);
    }

    public function show(Request $request, int $id): never
    {
        $sale = $this->findSale($id);
        $this->view('sales.show', [
            'title' => $sale->sale_number,
            'pageTitle' => 'Sale ' . $sale->sale_number,
            'sale' => $sale,
            ...$this->saleDetailPayload($sale),
            'canManage' => auth()->hasRole('admin'),
        ]);
    }

    public function edit(Request $request, int $id): never
    {
        $this->assertAdminOnly();

        $sale = $this->findSale($id);
        $this->view('sales.form', [
            'title' => 'Edit sale',
            'pageTitle' => 'Edit sale',
            'sale' => $sale,
            ...$this->saleDetailPayload($sale),
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $this->assertAdminOnly();

        $sale = $this->findSale($id);
        $data = $this->validate($request->all(), [
            'notes' => 'max:255',
            'status' => 'required',
        ]);

        $status = (string) $data['status'];
        if (!in_array($status, ['completed', 'cancelled'], true)) {
            $this->backWithError('Invalid sale status.', '/sales/' . $id . '/edit');
        }

        if ($status === 'cancelled' && (string) $sale->status !== 'cancelled') {
            try {
                (new SaleService())->voidSale($sale);
                $sale = Sale::findOrFail($id);
            } catch (RuntimeException $e) {
                $this->backWithError($e->getMessage(), '/sales/' . $id . '/edit');
            }
        }

        $sale->update([
            'notes' => $request->input('notes') ?: null,
            'status' => $status,
        ]);

        $this->backWithSuccess('Sale updated.', '/sales/' . $id);
    }

    public function destroy(Request $request, int $id): never
    {
        $this->assertAdminOnly();

        $sale = $this->findSale($id);
        try {
            (new SaleService())->voidSale($sale);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/sales');
        }

        $sale->delete();
        $this->backWithSuccess('Sale archived. Stock was restored where applicable. Restore or permanently delete it from Archives.', '/sales');
    }

    private function findSale(int $id): Sale
    {
        $sale = Sale::findOrFail($id);
        $this->assertSameBranch($sale);

        return $sale;
    }

    /** @return array{items: list<array<string, mixed>>, customer: ?Customer, cashier: ?User, branch: ?Branch, paymentLabel: string} */
    private function saleDetailPayload(Sale $sale): array
    {
        $items = Database::instance()->fetchAll(
            'SELECT si.*, p.name AS product_name, b.batch_number
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN batches b ON b.id = si.batch_id
             WHERE si.sale_id = :id AND si.deleted_at IS NULL',
            [':id' => (int) $sale->id],
        );

        $payment = Database::instance()->fetch(
            'SELECT method FROM payments
             WHERE payable_type = :type AND payable_id = :id AND deleted_at IS NULL
             ORDER BY id DESC LIMIT 1',
            [':type' => 'sale', ':id' => (int) $sale->id],
        );

        $method = (string) ($payment['method'] ?? ($sale->payment_status === 'credit' ? 'credit' : 'cash'));
        $paymentLabel = match ($method) {
            'cash' => 'Cash',
            'mobile_money' => 'Mobile money',
            'card' => 'Card',
            'credit' => 'Credit',
            default => ucfirst($method),
        };

        return [
            'items' => $items,
            'customer' => $sale->customer_id ? Customer::find((int) $sale->customer_id) : null,
            'cashier' => User::find((int) $sale->user_id),
            'branch' => Branch::find((int) $sale->branch_id),
            'paymentLabel' => $paymentLabel,
        ];
    }

    private function assertAdminOnly(): void
    {
        if (!auth()->hasRole('admin')) {
            abort(403, 'Only administrators can change or remove sales.');
        }
    }
}
