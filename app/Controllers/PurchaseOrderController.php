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
use RuntimeException;

final class PurchaseOrderController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT po.*, s.name AS supplier_name,
                    COALESCE((
                        SELECT SUM(poi.quantity_received)
                        FROM purchase_order_items poi
                        WHERE poi.purchase_order_id = po.id AND poi.deleted_at IS NULL
                    ), 0) AS received_total
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
        $payload = $this->formPayload(null);
        $this->view('purchase-orders.form', [
            'title' => 'New purchase order',
            'pageTitle' => 'New purchase order',
            ...$payload,
        ]);
    }

    public function store(Request $request): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot create purchase orders.');
        }

        $data = $this->validate($request->all(), [
            'supplier_id' => 'required|integer',
        ]);

        $supplier = Supplier::findOrFail((int) $data['supplier_id']);
        $branchId = $this->branchId();

        try {
            $lines = $this->buildLinesFromRequest($request);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/purchase-orders/create');
        }

        $subtotal = $this->linesSubtotal($lines);

        $po = PurchaseOrder::create([
            'branch_id' => $branchId,
            'supplier_id' => $supplier->id,
            'po_number' => NumberService::next('PO-', 'purchase_orders', 'po_number'),
            'status' => 'sent',
            'currency_code' => 'GHS',
            'exchange_rate_at_purchase' => 1,
            'subtotal' => $subtotal,
            'tax' => 0,
            'total_foreign' => $subtotal,
            'total_ghs' => $subtotal,
            'expected_date' => $request->input('expected_date') ?: null,
            'notes' => $request->input('notes'),
        ]);

        $this->replacePoItems((int) $po->id, $lines);

        $this->backWithSuccess('Purchase order created. Stock will increase only after verified receipt.', '/purchase-orders');
    }

    public function show(Request $request, int $id): never
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);
        $items = $this->orderItems($id);

        $this->view('purchase-orders.po-show', [
            'title' => (string) $order->po_number,
            'pageTitle' => 'Purchase order',
            'order' => $order,
            'items' => $items,
            'supplier' => Supplier::find((int) $order->supplier_id),
            'canReceive' => $this->canReceive($order, $items),
        ]);
    }

    public function edit(Request $request, int $id): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot edit purchase orders.');
        }

        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);

        if (!$this->poIsEditable($order)) {
            $this->backWithError('This purchase order cannot be edited after goods have been received.', '/purchase-orders/' . $id);
        }

        $payload = $this->formPayload($order);
        $this->view('purchase-orders.form', [
            'title' => 'Edit ' . $order->po_number,
            'pageTitle' => 'Edit purchase order',
            ...$payload,
        ]);
    }

    public function update(Request $request, int $id): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot edit purchase orders.');
        }

        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);

        if (!$this->poIsEditable($order)) {
            $this->backWithError('This purchase order cannot be edited after goods have been received.', '/purchase-orders/' . $id);
        }

        $data = $this->validate($request->all(), [
            'supplier_id' => 'required|integer',
        ]);

        $supplier = Supplier::findOrFail((int) $data['supplier_id']);
        if ((int) $supplier->id !== (int) $order->supplier_id) {
            $order->update(['supplier_id' => $supplier->id]);
        }

        try {
            $lines = $this->buildLinesFromRequest($request);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/purchase-orders/' . $id . '/edit');
        }

        $subtotal = $this->linesSubtotal($lines);

        $order->update([
            'subtotal' => $subtotal,
            'tax' => 0,
            'total_foreign' => $subtotal,
            'total_ghs' => $subtotal,
            'expected_date' => $request->input('expected_date') ?: null,
            'notes' => $request->input('notes'),
        ]);

        $this->replacePoItems((int) $order->id, $lines);

        $this->backWithSuccess('Purchase order updated.', '/purchase-orders');
    }

    public function receiveForm(Request $request, int $id): never
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);
        $items = $this->orderItems($id);

        if (!$this->canReceive($order, $items)) {
            $this->backWithError('Nothing left to receive on this purchase order.', '/purchase-orders/' . $id);
        }

        $this->view('purchase-orders.receive', [
            'title' => 'Receive ' . $order->po_number,
            'pageTitle' => 'Goods receipt',
            'order' => $order,
            'items' => $items,
            'supplier' => Supplier::find((int) $order->supplier_id),
        ]);
    }

    public function receive(Request $request, int $id): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot receive goods.');
        }

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
        $receivedAny = false;

        foreach ($itemIds as $i => $itemId) {
            $qty = (float) ($qtys[$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $item = PurchaseOrderItem::find((int) $itemId);
            if (!$item || (int) $item->purchase_order_id !== (int) $order->id) {
                continue;
            }

            $receivedAny = true;

            GoodsReceivedNoteItem::create([
                'grn_id' => $grn->id,
                'purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'batch_number' => $batches[$i] ?: ('B' . date('ymd') . $item->id),
                'expiry_date' => $expiries[$i] ?: date('Y-m-d', strtotime('+18 months')),
                'quantity' => $qty,
                'unit_cost' => $item->unit_cost,
            ]);

            Batch::create([
                'product_id' => $item->product_id,
                'branch_id' => $order->branch_id,
                'batch_number' => $batches[$i] ?: ('B' . date('ymd') . $item->id),
                'expiry_date' => $expiries[$i] ?: date('Y-m-d', strtotime('+18 months')),
                'quantity_received' => $qty,
                'quantity_remaining' => $qty,
                'unit_cost' => $item->unit_cost,
                'supplier_id' => $order->supplier_id,
            ]);

            $stock->syncLevelFromBatches((int) $item->product_id, (int) $order->branch_id);
            $item->update(['quantity_received' => (float) $item->quantity_received + $qty]);
        }

        if (!$receivedAny) {
            $grn->delete();
            $this->backWithError('Enter a quantity to receive for at least one line.', '/purchase-orders/' . $id . '/receive');
        }

        $remaining = Database::instance()->fetch(
            'SELECT COALESCE(SUM(quantity_ordered - quantity_received), 0) AS left_qty
             FROM purchase_order_items
             WHERE purchase_order_id = :id AND deleted_at IS NULL',
            [':id' => (int) $order->id],
        );
        if ((float) ($remaining['left_qty'] ?? 0) <= 0.0001) {
            $order->update(['status' => 'received']);
        }

        $this->backWithSuccess('Goods receipt verified. Stock increased.', '/purchase-orders');
    }

    public function destroy(Request $request, int $id): never
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->assertSameBranch($order);
        $order->delete();
        $this->backWithSuccess('Purchase order archived.', '/purchase-orders');
    }

    /** @return list<array<string, mixed>> */
    private function orderItems(int $orderId): array
    {
        return Database::instance()->fetchAll(
            'SELECT poi.*, p.name AS product_name, p.sku
             FROM purchase_order_items poi
             JOIN products p ON p.id = poi.product_id
             WHERE poi.purchase_order_id = :id AND poi.deleted_at IS NULL
             ORDER BY p.name ASC',
            [':id' => $orderId],
        );
    }

    /** @return array{order: ?PurchaseOrder, suppliers: list<Supplier>, products: list<Product>, lineCosts: array<int, float>, initialLines: list<array{product_id: int, quantity: float, unit_cost: float}>, preselectSupplierId: int} */
    private function formPayload(?PurchaseOrder $order): array
    {
        $branchId = $this->branchId();
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        $productIds = array_map(static fn(Product $p): int => (int) $p->id, $products);
        $lineCosts = $this->lastUnitCostMap($productIds, $branchId);

        $initialLines = [];
        $preselectSupplierId = 0;

        if ($order instanceof PurchaseOrder) {
            $preselectSupplierId = (int) $order->supplier_id;
            foreach ($this->orderItems((int) $order->id) as $row) {
                $initialLines[] = [
                    'product_id' => (int) $row['product_id'],
                    'quantity' => (float) $row['quantity_ordered'],
                    'unit_cost' => (float) $row['unit_cost'],
                ];
            }
        }

        return [
            'order' => $order,
            'suppliers' => Supplier::where('is_active', 1)->orderBy('name')->get(),
            'products' => $products,
            'lineCosts' => $lineCosts,
            'initialLines' => $initialLines,
            'preselectSupplierId' => $preselectSupplierId,
        ];
    }

    /** @param list<int> $productIds
     * @return array<int, float>
     */
    private function lastUnitCostMap(array $productIds, int $branchId): array
    {
        if ($productIds === []) {
            return [];
        }

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = 0.0;
        }

        $in = implode(',', array_map('intval', $productIds));
        $rows = Database::instance()->fetchAll(
            "SELECT poi.product_id, poi.unit_cost
             FROM purchase_order_items poi
             JOIN purchase_orders po ON po.id = poi.purchase_order_id
             WHERE po.branch_id = :b
               AND po.deleted_at IS NULL
               AND poi.deleted_at IS NULL
               AND poi.product_id IN ({$in})
             ORDER BY poi.id DESC",
            [':b' => $branchId],
        );

        foreach ($rows as $row) {
            $pid = (int) $row['product_id'];
            if ($map[$pid] <= 0 && (float) $row['unit_cost'] > 0) {
                $map[$pid] = (float) $row['unit_cost'];
            }
        }

        $batchRows = Database::instance()->fetchAll(
            "SELECT b.product_id, b.unit_cost
             FROM batches b
             INNER JOIN (
                 SELECT product_id, MAX(id) AS max_id
                 FROM batches
                 WHERE branch_id = :b AND deleted_at IS NULL AND product_id IN ({$in})
                 GROUP BY product_id
             ) latest ON latest.max_id = b.id
             WHERE b.unit_cost > 0",
            [':b' => $branchId],
        );

        foreach ($batchRows as $row) {
            $pid = (int) $row['product_id'];
            if ($map[$pid] <= 0) {
                $map[$pid] = (float) $row['unit_cost'];
            }
        }

        return $map;
    }

    /** @return list<array{product_id: int, qty: float, cost: float, line: float}> */
    private function buildLinesFromRequest(Request $request): array
    {
        $productIds = (array) $request->input('product_id', []);
        $qtys = (array) $request->input('quantity', []);
        $costs = (array) $request->input('unit_cost', []);

        $lines = [];
        foreach ($productIds as $i => $productId) {
            if (!$productId) {
                continue;
            }
            $qty = (float) ($qtys[$i] ?? 0);
            $cost = (float) ($costs[$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            if ($cost < 0) {
                throw new RuntimeException('Unit cost cannot be negative.');
            }
            $line = round($qty * $cost, 2);
            $lines[] = [
                'product_id' => (int) $productId,
                'qty' => $qty,
                'cost' => $cost,
                'line' => $line,
            ];
        }

        if ($lines === []) {
            throw new RuntimeException('Add at least one product line with quantity.');
        }

        return $lines;
    }

    /** @param list<array{product_id: int, qty: float, cost: float, line: float}> $lines */
    private function linesSubtotal(array $lines): float
    {
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += $line['line'];
        }

        return round($subtotal, 2);
    }

    /** @param list<array{product_id: int, qty: float, cost: float, line: float}> $lines */
    private function replacePoItems(int $poId, array $lines): void
    {
        $db = Database::instance();
        $db->execute(
            'DELETE FROM purchase_order_items WHERE purchase_order_id = :id',
            [':id' => $poId],
        );

        foreach ($lines as $line) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $poId,
                'product_id' => $line['product_id'],
                'quantity_ordered' => $line['qty'],
                'quantity_received' => 0,
                'unit_cost' => $line['cost'],
                'line_total' => $line['line'],
            ]);
        }
    }

    private function poIsEditable(PurchaseOrder $order): bool
    {
        if ((string) $order->status === 'received') {
            return false;
        }

        $row = Database::instance()->fetch(
            'SELECT COALESCE(SUM(quantity_received), 0) AS received
             FROM purchase_order_items
             WHERE purchase_order_id = :id AND deleted_at IS NULL',
            [':id' => (int) $order->id],
        );

        return (float) ($row['received'] ?? 0) <= 0.0001;
    }

    /** @param list<array<string, mixed>> $items */
    private function canReceive(PurchaseOrder $order, array $items): bool
    {
        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            $left = (float) $item['quantity_ordered'] - (float) $item['quantity_received'];
            if ($left > 0.0001) {
                return true;
            }
        }

        return false;
    }
}
