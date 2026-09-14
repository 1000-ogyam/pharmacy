<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\WholesaleOrder;
use App\Models\WholesaleOrderItem;
use App\Services\FefoService;
use App\Services\NumberService;
use App\Services\PricingService;
use App\Services\StockService;
use RuntimeException;

final class WholesaleController extends Controller
{
    public function index(Request $request): never
    {
        $db = Database::instance();
        $orders = $db->paginate(
            'SELECT w.*, c.name AS customer_name
             FROM wholesale_orders w
             JOIN customers c ON c.id = w.customer_id
             WHERE w.deleted_at IS NULL AND w.branch_id = :b
             ORDER BY w.id DESC',
            [':b' => $this->branchId()],
            page_number()
        );
        $quotes = $db->paginate(
            'SELECT q.*, c.name AS customer_name
             FROM quotations q
             JOIN customers c ON c.id = q.customer_id
             WHERE q.deleted_at IS NULL AND q.branch_id = :b
             ORDER BY q.id DESC',
            [':b' => $this->branchId()],
            page_number('qpage')
        );

        $this->view('wholesale.index', [
            'title' => 'Wholesale',
            'pageTitle' => 'Wholesale sales',
            'orders' => $orders['data'],
            'page' => $orders['page'],
            'pages' => $orders['pages'],
            'quotes' => $quotes['data'],
            'quotePage' => $quotes['page'],
            'quotePages' => $quotes['pages'],
        ]);
    }

    public function createQuote(Request $request): never
    {
        $branchId = $this->branchId();
        $stock = new StockService();
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        foreach ($products as $product) {
            $product->sellable = $stock->available((int) $product->id, $branchId);
        }

        $this->view('wholesale.quote-form', [
            'title' => 'New quotation',
            'pageTitle' => 'New quotation',
            'customers' => Customer::where('type', 'wholesale')->where('branch_id', $branchId)->orderBy('name')->get(),
            'products' => $products,
        ]);
    }

    public function storeQuote(Request $request): never
    {
        $data = $this->validate($request->all(), ['customer_id' => 'required|integer']);
        $customer = Customer::findOrFail((int) $data['customer_id']);
        $this->assertSameBranch($customer);
        $pricing = new PricingService();
        $branchId = (int) auth()->branchId();

        $productIds = (array) $request->input('product_id', []);
        $qtys = (array) $request->input('quantity', []);
        $subtotal = 0;
        $lines = [];

        foreach ($productIds as $i => $pid) {
            if (!$pid) {
                continue;
            }
            $qty = (float) ($qtys[$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $price = $pricing->unitPrice((int) $pid, $branchId, $customer);
            $line = $qty * $price;
            $subtotal += $line;
            $lines[] = ['product_id' => (int) $pid, 'qty' => $qty, 'price' => $price, 'line' => $line];
        }

        if ($lines === []) {
            $this->backWithError('Add at least one product with a quantity greater than zero.', '/wholesale/quotations/create');
        }

        $quote = Quotation::create([
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'quotation_number' => NumberService::next('QT-', 'quotations', 'quotation_number'),
            'status' => 'sent',
            'valid_until' => date('Y-m-d', strtotime('+14 days')),
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ]);

        foreach ($lines as $line) {
            QuotationItem::create([
                'quotation_id' => $quote->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['price'],
                'line_total' => $line['line'],
            ]);
        }

        $this->backWithSuccess('Quotation created.', '/wholesale');
    }

    public function convert(Request $request, int $id): never
    {
        $quote = Quotation::findOrFail($id);
        $this->assertSameBranch($quote);

        if ($quote->status === 'converted') {
            $this->backWithError('This quotation has already been converted.', '/wholesale');
        }

        $customer = Customer::findOrFail((int) $quote->customer_id);
        $this->assertSameBranch($customer);

        if ($customer->availableCredit() < (float) $quote->total && (float) $quote->total > 0) {
            $this->backWithError('Customer credit limit is insufficient to convert this quotation.', '/wholesale');
        }

        $items = QuotationItem::where('quotation_id', (int) $quote->id)->get();
        if ($items === []) {
            $this->backWithError('This quotation has no lines to convert.', '/wholesale');
        }

        $branchId = $this->branchId();
        $stock = new StockService();
        $needed = [];

        foreach ($items as $item) {
            $productId = (int) $item->product_id;
            $needed[$productId] = ($needed[$productId] ?? 0.0) + (float) $item->quantity;
        }

        try {
            foreach ($needed as $productId => $qty) {
                $available = $stock->available($productId, $branchId);
                if ($available + 0.0001 < $qty) {
                    $name = Product::find($productId)?->name ?? ('product #' . $productId);
                    throw new RuntimeException(
                        'Insufficient sellable stock for ' . $name
                        . '. Requested ' . format_qty($qty)
                        . ', available ' . format_qty($available) . '.'
                    );
                }
            }

            $fefo = new FefoService();
            $db = Database::instance();
            $db->beginTransaction();

            try {
                $order = WholesaleOrder::create([
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'quotation_id' => $quote->id,
                    'order_number' => NumberService::next('WO-', 'wholesale_orders', 'order_number'),
                    'status' => 'invoiced',
                    'subtotal' => $quote->subtotal,
                    'discount' => $quote->discount,
                    'tax' => $quote->tax,
                    'total' => $quote->total,
                    'credit_used' => $quote->total,
                ]);

                foreach ($items as $item) {
                    $allocation = $fefo->allocate((int) $item->product_id, $branchId, (float) $item->quantity);
                    foreach ($allocation as $row) {
                        WholesaleOrderItem::create([
                            'wholesale_order_id' => $order->id,
                            'product_id' => $item->product_id,
                            'batch_id' => $row['batch']->id,
                            'quantity' => $row['quantity'],
                            'unit_price' => $item->unit_price,
                            'line_total' => $row['quantity'] * (float) $item->unit_price,
                        ]);
                    }
                    $stock->decreaseFromAllocation($allocation, (int) $item->product_id, $branchId);
                }

                $customer->update(['credit_balance' => (float) $customer->credit_balance + (float) $quote->total]);
                $quote->update(['status' => 'converted']);

                Invoice::create([
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'wholesale_order_id' => $order->id,
                    'invoice_number' => NumberService::next('INV-', 'invoices', 'invoice_number'),
                    'invoice_type' => 'wholesale',
                    'status' => 'issued',
                    'subtotal' => $quote->subtotal,
                    'tax' => $quote->tax,
                    'total' => $quote->total,
                    'due_date' => date('Y-m-d', strtotime('+30 days')),
                ]);

                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/wholesale');
        }

        $this->backWithSuccess('Quotation converted to a wholesale order and invoice.', '/wholesale');
    }
}
