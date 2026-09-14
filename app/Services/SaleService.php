<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Sms\QueuedSmsService;
use RuntimeException;

final class SaleService
{
    public function __construct(
        private readonly FefoService $fefo = new FefoService(),
        private readonly PricingService $pricing = new PricingService(),
        private readonly StockService $stock = new StockService(),
        private readonly LedgerService $ledger = new LedgerService(),
        private readonly QueuedSmsService $sms = new QueuedSmsService(),
    ) {
    }

    public function checkout(array $payload): Sale
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $userId = (int) (auth()->id() ?? 0);
        $items = $payload['items'] ?? [];

        if ($items === []) {
            throw new RuntimeException('Cart is empty.');
        }

        $customer = !empty($payload['customer_id']) ? Customer::find((int) $payload['customer_id']) : null;
        if ($customer instanceof Customer && (int) $customer->branch_id !== $branchId) {
            throw new RuntimeException('Customer is not registered at this branch.');
        }
        $discount = (float) ($payload['discount'] ?? 0);
        $method = (string) ($payload['payment_method'] ?? 'cash');
        $taxRate = 0.0;

        $db = Database::instance();
        $db->beginTransaction();

        try {
            $prepared = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (float) $item['quantity'];
                $allocation = $this->fefo->allocate($productId, $branchId, $qty);
                $unitPrice = $this->pricing->unitPrice($productId, $branchId, $customer);

                foreach ($allocation as $row) {
                    $line = $row['quantity'] * $unitPrice;
                    $prepared[] = [
                        'product_id' => $productId,
                        'batch' => $row['batch'],
                        'quantity' => $row['quantity'],
                        'unit_price' => $unitPrice,
                        'line_total' => $line,
                    ];
                    $subtotal += $line;
                }
            }

            $tax = round($subtotal * $taxRate, 2);
            $total = max(0, round($subtotal - $discount + $tax, 2));
            $useCredit = $method === 'credit';

            if ($useCredit) {
                if (!$customer instanceof Customer) {
                    throw new RuntimeException('A customer account is required for credit sales.');
                }
                if ($customer->availableCredit() < $total) {
                    throw new RuntimeException('Customer credit limit exceeded.');
                }
            }

            $sale = Sale::create([
                'branch_id' => $branchId,
                'customer_id' => $customer?->id,
                'user_id' => $userId,
                'sale_number' => NumberService::next('POS-', 'sales', 'sale_number'),
                'sale_type' => $payload['sale_type'] ?? 'retail',
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'payment_status' => $useCredit ? 'credit' : 'paid',
                'notes' => $payload['notes'] ?? null,
            ]);

            $byProduct = [];
            foreach ($prepared as $row) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product_id'],
                    'batch_id' => $row['batch']->id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'discount' => 0,
                    'line_total' => $row['line_total'],
                ]);

                $byProduct[$row['product_id']][] = $row;
            }

            foreach ($byProduct as $productId => $rows) {
                $this->stock->decreaseFromAllocation($rows, (int) $productId, $branchId);
            }

            if ($useCredit && $customer instanceof Customer) {
                $customer->update([
                    'credit_balance' => (float) $customer->credit_balance + $total,
                ]);
            } else {
                Payment::create([
                    'branch_id' => $branchId,
                    'payable_type' => 'sale',
                    'payable_id' => $sale->id,
                    'method' => $method,
                    'amount' => $total,
                    'reference' => $sale->sale_number,
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);
                $this->ledger->cashIn($branchId, $total, $method, 'POS ' . $sale->sale_number, $sale->sale_number);
            }

            $this->ledger->post($branchId, '4000', 0, $total, 'Sale ' . $sale->sale_number, 'sale', (int) $sale->id);
            $this->ledger->post($branchId, $useCredit ? '1100' : '1000', $total, 0, 'Sale ' . $sale->sale_number, 'sale', (int) $sale->id);

            Invoice::create([
                'branch_id' => $branchId,
                'customer_id' => $customer?->id,
                'sale_id' => $sale->id,
                'invoice_number' => NumberService::next('INV-', 'invoices', 'invoice_number'),
                'invoice_type' => $sale->sale_type,
                'status' => 'issued',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'due_date' => $useCredit ? date('Y-m-d', strtotime('+14 days')) : date('Y-m-d'),
            ]);

            if ($customer && $customer->phone && (int) $customer->sms_opt_in) {
                $this->sms->sendTemplate(
                    (string) $customer->phone,
                    'receipt',
                    [
                        'customer_name' => (string) $customer->name,
                        'invoice_no' => (string) $sale->sale_number,
                        'amount' => money($total),
                    ],
                    (int) $customer->id
                );
            }

            $db->commit();
            return $sale;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
