<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Customer;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use RuntimeException;

final class ReturnService
{
    public function __construct(
        private readonly StockService $stock = new StockService(),
        private readonly LedgerService $ledger = new LedgerService(),
    ) {
    }

    public function saleAlreadyReturned(int $saleId): bool
    {
        $row = Database::instance()->fetch(
            'SELECT id FROM returns WHERE sale_id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $saleId],
        );

        return $row !== null;
    }

    public function processFullReturn(Sale $sale, string $reason): ProductReturn
    {
        if ((string) $sale->status !== 'completed') {
            throw new RuntimeException('Only completed sales can be returned.');
        }

        if ($this->saleAlreadyReturned((int) $sale->id)) {
            throw new RuntimeException('This sale already has a return.');
        }

        $items = Database::instance()->fetchAll(
            'SELECT * FROM sale_items WHERE sale_id = :id AND deleted_at IS NULL',
            [':id' => (int) $sale->id],
        );

        if ($items === []) {
            throw new RuntimeException('This sale has no line items to return.');
        }

        $db = Database::instance();
        $db->beginTransaction();

        try {
            $ret = ProductReturn::create([
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_number' => NumberService::next('RTN-', 'returns', 'return_number'),
                'reason' => $reason !== '' ? $reason : 'Customer return',
                'status' => 'approved',
                'total' => $sale->total,
            ]);

            foreach ($items as $item) {
                ReturnItem::create([
                    'return_id' => $ret->id,
                    'sale_item_id' => $item['id'],
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                ]);
                $this->stock->increase(
                    (int) $item['product_id'],
                    (int) $sale->branch_id,
                    (int) $item['batch_id'],
                    (float) $item['quantity'],
                );
            }

            $this->reverseSaleFinancials($sale, $ret);

            $sale->update([
                'status' => 'returned',
                'payment_status' => 'refunded',
            ]);

            $db->commit();

            return $ret;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function reverseSaleFinancials(Sale $sale, ProductReturn $ret): void
    {
        $branchId = (int) $sale->branch_id;
        $total = (float) $sale->total;
        $ref = (string) $ret->return_number;
        $paymentStatus = (string) $sale->payment_status;
        $useCredit = $paymentStatus === 'credit';
        $onInvoice = $paymentStatus === 'invoice';

        if ($useCredit && !empty($sale->customer_id)) {
            $customer = Customer::find((int) $sale->customer_id);
            if ($customer instanceof Customer) {
                $customer->update([
                    'credit_balance' => max(0, round((float) $customer->credit_balance - $total, 2)),
                ]);
            }
        } elseif (!$onInvoice) {
            $payment = Database::instance()->fetch(
                'SELECT method FROM payments
                 WHERE payable_type = :type AND payable_id = :id AND deleted_at IS NULL
                 ORDER BY id DESC LIMIT 1',
                [':type' => 'sale', ':id' => (int) $sale->id],
            );
            if ($payment !== null) {
                $method = (string) ($payment['method'] ?? 'cash');
                $this->ledger->cashOut($branchId, $total, $method, 'Return ' . $ref, $ref);
            }
        }

        $balanceAccount = ($useCredit || $onInvoice) ? '1100' : '1000';

        $this->ledger->post($branchId, '4000', $total, 0, 'Return ' . $ref, 'return', (int) $ret->id);
        $this->ledger->post(
            $branchId,
            $balanceAccount,
            0,
            $total,
            'Return ' . $ref,
            'return',
            (int) $ret->id,
        );
    }
}
