<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\NumberService;
use App\Services\StockService;

final class PurchaseOrderController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT po.*, s.name AS supplier_name
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             WHERE po.deleted_at IS NULL AND po.branch_id = :b
             ORDER BY po.id DESC',
            [':b' => $this->branchId()]
        );

        $this->view('purchase-orders.index', [
            'title' => 'Purchase orders',
            'pageTitle' => 'Purchase orders',
            'orders' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('purchase-orders.form', [
            'title' => 'New purchase order',
            'pageTitle' => 'New purchase order',
            'suppliers' => Supplier::where('is_active', 1)->orderBy('name')->get(),
            'products' => Product::where('is_active', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'supplier_id' => 'required|integer',
            'currency_code' => 'required',
            'exchange_rate_at_purchase' => 'required|numeric',
        ]);

        $productIds = (array) $request->input('product_id', []);
        $qtys = (array) $request->input('quantity', []);
        $costs = (array) $request->input('unit_cost', []);

        $subtotal = 0.0;
        $lines = [];
        foreach ($productIds as $i => $productId) {
            if (!$productId) {
                continue;
            }
            $qty = (float) ($qtys[$i] ?? 0);
            $cost = (float) ($costs[$i] ?? 0);
            $line = $qty * $cost;
            $subtotal += $line;
            $lines[] = ['product_id' => (int) $productId, 'qty' => $qty, 'cost' => $cost, 'line' => $line];
        }

        $rate = (float) $data['exchange_rate_at_purchase'];
        $po = PurchaseOrder::create([
            'branch_id' => auth()->branchId(),
            'supplier_id' => $data['supplier_id'],
            'po_number' => NumberService::next('PO-', 'purchase_orders', 'po_number'),
            'status' => 'sent',
            'currency_code' => $data['currency_code'],
            'exchange_rate_at_purchase' => $rate,
            'subtotal' => $subtotal,
            'tax' => 0,
            'total_foreign' => $subtotal,
            'total_ghs' => $subtotal * $rate,
            'expected_date' => $request->input('expected_date'),
            'notes' => $request->input('notes'),
        ]);

        foreach ($lines as $line) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $line['product_id'],
                'quantity_ordered' => $line['qty'],
                'quantity_received' => 0,
                'unit_cost' => $line['cost'],
                'line_total' => $line['line'],
            ]);
        }

        $this->backWithSuccess('Purchase order created. Stock will increase only after verified receipt.', '/purchase-orders');
    }

    public function show(Request $request, int $id): never
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);
        $items = Database::instance()->fetchAll(
            'SELECT poi.*, p.name AS product_name
             FROM purchase_order_items poi
             JOIN products p ON p.id = poi.product_id
             WHERE poi.purchase_order_id = :id AND poi.deleted_at IS NULL',
            [':id' => $id]
        );

        $this->view('purchase-orders.show', [
            'title' => $order->po_number,
            'pageTitle' => $order->po_number,
            'order' => $order,
            'items' => $items,
            'supplier' => Supplier::find((int) $order->supplier_id),
        ]);
    }

    public function receive(Request $request, int $id): never
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);
        $itemIds = (array) $request->input('item_id', []);
        $qtys = (array) $request->input('qty_received', []);
        $batches = (array) $request->input('batch_number', []);
        $expiries = (array) $request->input('expiry_date', []);

        $grn = GoodsReceivedNote::create([
            'purchase_order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'grn_number' => NumberService::next('GRN-', 'goods_received_notes', 'grn_number'),
            'received_by' => auth()->id(),
            'verified_by' => auth()->id(),
            'status' => 'verified',
            'received_at' => date('Y-m-d H:i:s'),
        ]);

        $stock = new StockService();

        foreach ($itemIds as $i => $itemId) {
            $qty = (float) ($qtys[$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $item = PurchaseOrderItem::find((int) $itemId);
            if (!$item) {
                continue;
            }

            GoodsReceivedNoteItem::create([
                'grn_id' => $grn->id,
                'purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'batch_number' => $batches[$i] ?: ('B' . date('ymd') . $item->id),
                'expiry_date' => $expiries[$i] ?: date('Y-m-d', strtotime('+18 months')),
                'quantity' => $qty,
                'unit_cost' => $item->unit_cost,
            ]);

            $batch = Batch::create([
                'product_id' => $item->product_id,
                'branch_id' => $order->branch_id,
                'batch_number' => $batches[$i] ?: ('B' . date('ymd') . $item->id),
                'expiry_date' => $expiries[$i] ?: date('Y-m-d', strtotime('+18 months')),
                'quantity_received' => $qty,
                'quantity_remaining' => $qty,
                'unit_cost' => $item->unit_cost,
                'supplier_id' => $order->supplier_id,
            ]);

            $stock->adjustLevel((int) $item->product_id, (int) $order->branch_id, $qty);
            $item->update(['quantity_received' => (float) $item->quantity_received + $qty]);
            unset($batch);
        }

        $order->update(['status' => 'received']);
        $this->backWithSuccess('Goods receipt verified. Stock increased.', '/purchase-orders');
    }

    public function edit(Request $request, int $id): never
    {
        $this->show($request, $id);
    }

    public function update(Request $request, int $id): never
    {
        $this->show($request, $id);
    }

    public function destroy(Request $request, int $id): never
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);
        $order->delete();
        $this->backWithSuccess('Purchase order archived.', '/purchase-orders');
    }
}
