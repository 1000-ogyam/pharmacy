<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\WholesaleOrder;
use App\Models\WholesaleOrderItem;
use App\Services\FefoService;
use App\Services\NumberService;
use App\Services\PdfService;
use App\Services\PricingService;
use App\Services\SaleService;
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
        $this->view('wholesale.quote-form', [
            'title' => 'New quotation',
            'pageTitle' => 'New quotation',
            ...$this->quoteFormPayload(null),
            'preselectCustomerId' => (int) $request->query('customer_id', 0),
        ]);
    }

    public function editQuote(Request $request, int $id): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot edit quotations.');
        }

        $quote = Quotation::findOrFail($id);
        $this->assertSameBranch($quote);

        if ($this->quoteIsConverted($quote)) {
            $this->backWithError('Converted quotations cannot be edited.', '/wholesale/quotations/' . $id);
        }

        $payload = $this->quoteFormPayload($quote);
        $this->view('wholesale.quote-form', [
            'title' => 'Edit quotation',
            'pageTitle' => 'Edit quotation',
            ...$payload,
            'quote' => $quote,
            'preselectCustomerId' => (int) $quote->customer_id,
            'returnTo' => '/wholesale/quotations/' . $id . '/edit',
        ]);
    }

    public function storeQuote(Request $request): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot create quotations.');
        }

        $data = $this->validate($request->all(), ['customer_id' => 'required|integer']);
        $customer = Customer::findOrFail((int) $data['customer_id']);
        $this->assertSameBranch($customer);
        $branchId = $this->branchId();

        try {
            $lines = $this->buildQuoteLinesFromRequest($request, $branchId);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/wholesale/quotations/create');
        }

        $subtotal = $this->quoteSubtotal($lines);

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

        $this->replaceQuotationItems((int) $quote->id, $lines);

        $this->backWithSuccess('Quotation created.', '/wholesale');
    }

    public function updateQuote(Request $request, int $id): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot edit quotations.');
        }

        $quote = Quotation::findOrFail($id);
        $this->assertSameBranch($quote);

        if ($this->quoteIsConverted($quote)) {
            $this->backWithError('Converted quotations cannot be edited.', '/wholesale/quotations/' . $id);
        }

        $data = $this->validate($request->all(), ['customer_id' => 'required|integer']);
        $customer = Customer::findOrFail((int) $data['customer_id']);
        $this->assertSameBranch($customer);
        $branchId = $this->branchId();

        try {
            $lines = $this->buildQuoteLinesFromRequest($request, $branchId);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/wholesale/quotations/' . $id . '/edit');
        }

        $subtotal = $this->quoteSubtotal($lines);

        $quote->update([
            'customer_id' => $customer->id,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ]);

        $this->replaceQuotationItems((int) $quote->id, $lines);

        $this->backWithSuccess('Quotation updated.', '/wholesale');
    }

    public function showQuote(Request $request, int $id): never
    {
        $quote = Quotation::findOrFail($id);
        $this->assertSameBranch($quote);

        $items = Database::instance()->fetchAll(
            'SELECT qi.*, p.name AS product_name, p.sku
             FROM quotation_items qi
             JOIN products p ON p.id = qi.product_id
             WHERE qi.quotation_id = :id AND qi.deleted_at IS NULL
             ORDER BY p.name ASC',
            [':id' => (int) $quote->id],
        );

        $customer = Customer::find((int) $quote->customer_id);
        $linkedOrder = Database::instance()->fetch(
            'SELECT id, order_number FROM wholesale_orders
             WHERE quotation_id = :qid AND deleted_at IS NULL
             LIMIT 1',
            [':qid' => (int) $quote->id],
        );

        $this->view('wholesale.quote-show', [
            'title' => (string) $quote->quotation_number,
            'pageTitle' => 'Quotation',
            'quote' => $quote,
            'customer' => $customer,
            'items' => $items,
            'linkedOrder' => $linkedOrder ?: null,
        ]);
    }

    public function showOrder(Request $request, int $id): never
    {
        $order = WholesaleOrder::findOrFail($id);
        $this->assertSameBranch($order);

        $items = Database::instance()->fetchAll(
            'SELECT oi.*, p.name AS product_name, p.sku, b.batch_number
             FROM wholesale_order_items oi
             JOIN products p ON p.id = oi.product_id
             LEFT JOIN batches b ON b.id = oi.batch_id
             WHERE oi.wholesale_order_id = :id AND oi.deleted_at IS NULL
             ORDER BY p.name ASC, b.batch_number ASC',
            [':id' => (int) $order->id],
        );

        $customer = Customer::find((int) $order->customer_id);
        $quotation = !empty($order->quotation_id)
            ? Quotation::find((int) $order->quotation_id)
            : null;
        $invoice = Database::instance()->fetch(
            'SELECT id, invoice_number, status, due_date
             FROM invoices
             WHERE wholesale_order_id = :oid AND deleted_at IS NULL
             LIMIT 1',
            [':oid' => (int) $order->id],
        );

        $this->view('wholesale.order-show', [
            'title' => (string) $order->order_number,
            'pageTitle' => 'Wholesale order',
            'order' => $order,
            'customer' => $customer,
            'items' => $items,
            'quotation' => $quotation,
            'invoice' => $invoice ?: null,
        ]);
    }

    public function orderInvoice(Request $request, int $id): never
    {
        $payload = $this->orderInvoicePayload($id);
        $payload['standalone'] = true;
        $payload['autoPrint'] = (string) $request->query('print', '') === '1';
        Response::html(View::render('wholesale.invoice', $payload, null));
    }

    public function orderInvoicePdf(Request $request, int $id): never
    {
        $payload = $this->orderInvoicePayload($id);
        $payload['pdf'] = true;
        $number = (string) ($payload['invoiceRow']['invoice_number'] ?? $payload['order']->order_number);
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $number) ?: 'invoice';
        (new PdfService())->render('wholesale.invoice', $payload, $safe . '.pdf', true);
    }

    /** @return array<string, mixed> */
    private function orderInvoicePayload(int $id): array
    {
        $order = WholesaleOrder::findOrFail($id);
        $this->assertSameBranch($order);

        $invoiceRow = Database::instance()->fetch(
            'SELECT *
             FROM invoices
             WHERE wholesale_order_id = :oid AND deleted_at IS NULL
             LIMIT 1',
            [':oid' => (int) $order->id],
        );

        if ($invoiceRow === null) {
            abort(404, 'No invoice found for this order.');
        }

        $items = Database::instance()->fetchAll(
            'SELECT oi.*, p.name AS product_name, p.sku, b.batch_number
             FROM wholesale_order_items oi
             JOIN products p ON p.id = oi.product_id
             LEFT JOIN batches b ON b.id = oi.batch_id
             WHERE oi.wholesale_order_id = :id AND oi.deleted_at IS NULL
             ORDER BY p.name ASC, b.batch_number ASC',
            [':id' => (int) $order->id],
        );

        $customer = Customer::find((int) $order->customer_id);
        $branch = Branch::find((int) $order->branch_id);

        return [
            'title' => 'Invoice ' . $invoiceRow['invoice_number'],
            'pageTitle' => 'Wholesale invoice',
            'order' => $order,
            'invoiceRow' => $invoiceRow,
            'customer' => $customer,
            'branch' => $branch,
            'items' => $items,
            'pdf' => false,
        ];
    }

    public function convert(Request $request, int $id): never
    {
        try {
            $quote = Quotation::findOrFail($id);
            $this->assertSameBranch($quote);

            if ((string) $quote->status === 'converted') {
                $this->backWithError('This quotation has already been converted.', '/wholesale');
            }

            $customer = Customer::findOrFail((int) $quote->customer_id);
            $this->assertSameBranch($customer);

            $total = (float) $quote->total;
            $creditUsed = $this->creditToApplyOnConvert($customer, $total);

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
                    'total' => $total,
                    'credit_used' => $creditUsed,
                ]);

                $saleLines = [];
                foreach ($items as $item) {
                    $allocation = $fefo->allocate((int) $item->product_id, $branchId, (float) $item->quantity);
                    foreach ($allocation as $row) {
                        $lineTotal = round($row['quantity'] * (float) $item->unit_price, 2);
                        WholesaleOrderItem::create([
                            'wholesale_order_id' => $order->id,
                            'product_id' => $item->product_id,
                            'batch_id' => $row['batch']->id,
                            'quantity' => $row['quantity'],
                            'unit_price' => $item->unit_price,
                            'line_total' => $lineTotal,
                        ]);
                        $saleLines[] = [
                            'product_id' => (int) $item->product_id,
                            'batch_id' => (int) $row['batch']->id,
                            'quantity' => (float) $row['quantity'],
                            'unit_price' => (float) $item->unit_price,
                            'line_total' => $lineTotal,
                        ];
                    }
                    $stock->decreaseFromAllocation($allocation, (int) $item->product_id, $branchId);
                }

                $userId = (int) (auth()->id() ?? 0);
                if ($userId <= 0) {
                    throw new RuntimeException('You must be signed in to convert a quotation.');
                }

                $sale = (new SaleService())->recordWholesaleConversion(
                    $branchId,
                    $userId,
                    $customer,
                    (float) $quote->subtotal,
                    (float) $quote->discount,
                    (float) $quote->tax,
                    $total,
                    $creditUsed,
                    $saleLines,
                    (string) $order->order_number,
                );

                if ($creditUsed > 0) {
                    $customer->update([
                        'credit_balance' => (float) $customer->credit_balance + $creditUsed,
                    ]);
                }

                $quote->update(['status' => 'converted']);

                Invoice::create([
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'wholesale_order_id' => $order->id,
                    'invoice_number' => NumberService::next('INV-', 'invoices', 'invoice_number'),
                    'invoice_type' => 'wholesale',
                    'status' => 'issued',
                    'subtotal' => $quote->subtotal,
                    'tax' => $quote->tax,
                    'total' => $total,
                    'due_date' => date('Y-m-d', strtotime('+30 days')),
                ]);

                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->backWithError($e->getMessage(), '/wholesale');
        }

        $this->backWithSuccess('Quotation converted to a wholesale order and invoice.', '/wholesale');
    }

    private function creditToApplyOnConvert(Customer $customer, float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        $creditLimit = (float) $customer->credit_limit;
        if ($creditLimit <= 0) {
            return 0.0;
        }

        if ($customer->availableCredit() + 0.01 < $total) {
            throw new RuntimeException(
                'Customer credit limit is insufficient to convert this quotation. '
                . 'Available credit: ' . money($customer->availableCredit()) . '.'
            );
        }

        return $total;
    }

    public function destroyQuote(Request $request, int $id): never
    {
        if (!auth()->hasRole('admin')) {
            abort(403, 'Only administrators can delete quotations.');
        }

        $quote = Quotation::findOrFail($id);
        $this->assertSameBranch($quote);

        if ((string) $quote->status === 'converted') {
            $linked = Database::instance()->fetch(
                'SELECT id FROM wholesale_orders WHERE quotation_id = :qid AND deleted_at IS NULL LIMIT 1',
                [':qid' => (int) $quote->id],
            );
            if ($linked !== null) {
                $this->backWithError(
                    'This quotation was converted to an order. Archive the order first or leave the quotation on file.',
                    '/wholesale',
                );
            }
        }

        $quote->delete();
        $this->backWithSuccess('Quotation archived.', '/wholesale');
    }

    /** @return list<Customer> */
    private function branchCustomers(int $branchId): array
    {
        return Customer::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();
    }

    /** @return array{customers: list<Customer>, products: list<Product>, linePrices: array<int, float>, initialLines: list<array{product_id: int, quantity: float}>} */
    private function quoteFormPayload(?Quotation $quote): array
    {
        $branchId = $this->branchId();
        $stock = new StockService();
        $pricing = new PricingService();
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        $linePrices = [];

        foreach ($products as $product) {
            $productId = (int) $product->id;
            $product->sellable = $stock->available($productId, $branchId);
            $linePrices[$productId] = $pricing->catalogPrice($productId, $branchId);
        }

        $initialLines = [];
        if ($quote instanceof Quotation) {
            $rows = Database::instance()->fetchAll(
                'SELECT product_id, quantity FROM quotation_items
                 WHERE quotation_id = :id AND deleted_at IS NULL
                 ORDER BY id ASC',
                [':id' => (int) $quote->id],
            );
            foreach ($rows as $row) {
                $initialLines[] = [
                    'product_id' => (int) $row['product_id'],
                    'quantity' => (float) $row['quantity'],
                ];
            }
        }

        return [
            'customers' => $this->branchCustomers($branchId),
            'products' => $products,
            'linePrices' => $linePrices,
            'initialLines' => $initialLines,
        ];
    }

    /** @return list<array{product_id: int, qty: float, price: float, line: float}> */
    private function buildQuoteLinesFromRequest(Request $request, int $branchId): array
    {
        $pricing = new PricingService();
        $productIds = (array) $request->input('product_id', []);
        $qtys = (array) $request->input('quantity', []);
        $lines = [];

        foreach ($productIds as $i => $pid) {
            if (!$pid) {
                continue;
            }
            $qty = (float) ($qtys[$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $price = $pricing->catalogPrice((int) $pid, $branchId);
            $lines[] = [
                'product_id' => (int) $pid,
                'qty' => $qty,
                'price' => $price,
                'line' => $qty * $price,
            ];
        }

        if ($lines === []) {
            throw new RuntimeException('Add at least one product with a quantity greater than zero.');
        }

        return $lines;
    }

    /** @param list<array{product_id: int, qty: float, price: float, line: float}> $lines */
    private function quoteSubtotal(array $lines): float
    {
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['line'];
        }

        return round($subtotal, 2);
    }

    /** @param list<array{product_id: int, qty: float, price: float, line: float}> $lines */
    private function replaceQuotationItems(int $quoteId, array $lines): void
    {
        Database::instance()->execute(
            'DELETE FROM quotation_items WHERE quotation_id = :id',
            [':id' => $quoteId],
        );

        foreach ($lines as $line) {
            QuotationItem::create([
                'quotation_id' => $quoteId,
                'product_id' => $line['product_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['price'],
                'line_total' => $line['line'],
            ]);
        }
    }

    private function quoteIsConverted(Quotation $quote): bool
    {
        return (string) $quote->status === 'converted';
    }
}
