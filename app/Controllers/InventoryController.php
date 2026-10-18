<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\FefoService;
use App\Services\NumberService;
use App\Services\StockService;
use RuntimeException;

final class InventoryController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = $this->branchId();
        $result = Database::instance()->paginate(
            'SELECT p.id, p.sku, p.name, p.category, sl.quantity, sl.reorder_level
             FROM products p
             LEFT JOIN stock_levels sl ON sl.product_id = p.id AND sl.branch_id = :b AND sl.deleted_at IS NULL
             WHERE p.deleted_at IS NULL
             ORDER BY sl.quantity ASC, p.name ASC',
            [':b' => $branchId],
        );

        $this->view('inventory.index', [
            'title' => 'Inventory',
            'pageTitle' => 'Stock levels',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function reorderForm(Request $request, int $productId): never
    {
        $product = Product::findOrFail($productId);
        $branchId = $this->branchId();
        $level = StockLevel::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        $this->view('inventory.reorder-form', [
            'title' => 'Reorder level',
            'pageTitle' => 'Reorder level',
            'product' => $product,
            'reorderLevel' => $level ? (float) $level->reorder_level : 10.0,
        ]);
    }

    public function reorderUpdate(Request $request, int $productId): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot change reorder levels.');
        }

        $product = Product::findOrFail($productId);
        $data = $this->validate($request->all(), [
            'reorder_level' => 'required|numeric',
        ]);

        $reorder = max(0, (float) $data['reorder_level']);
        $branchId = $this->branchId();

        $level = StockLevel::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$level instanceof StockLevel) {
            StockLevel::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity' => (new StockService())->onHandQuantity($productId, $branchId),
                'reorder_level' => $reorder,
            ]);
        } else {
            $level->update(['reorder_level' => $reorder]);
        }

        $this->backWithSuccess('Reorder level updated for ' . $product->name . '.', '/inventory');
    }

    public function transfersIndex(Request $request): never
    {
        $branchId = $this->branchId();
        $result = Database::instance()->paginate(
            'SELECT st.*,
                    fb.name AS from_name,
                    tb.name AS to_name,
                    (SELECT COUNT(*) FROM stock_transfer_items sti
                     WHERE sti.stock_transfer_id = st.id AND sti.deleted_at IS NULL) AS line_count
             FROM stock_transfers st
             JOIN branches fb ON fb.id = st.from_branch_id
             JOIN branches tb ON tb.id = st.to_branch_id
             WHERE st.deleted_at IS NULL
               AND (st.from_branch_id = :from_b OR st.to_branch_id = :to_b)
             ORDER BY st.created_at DESC',
            [':from_b' => $branchId, ':to_b' => $branchId],
        );

        $this->view('inventory.transfers-index', [
            'title' => 'Stock transfers',
            'pageTitle' => 'Stock transfers',
            'transfers' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function transferShow(Request $request, int $id): never
    {
        $branchId = $this->branchId();
        $transfer = StockTransfer::findOrFail($id);
        if ((int) $transfer->from_branch_id !== $branchId && (int) $transfer->to_branch_id !== $branchId) {
            abort(403, 'This transfer is not available at your branch.');
        }

        $lines = Database::instance()->fetchAll(
            'SELECT sti.*, p.name AS product_name, p.sku, b.batch_number, b.expiry_date
             FROM stock_transfer_items sti
             JOIN products p ON p.id = sti.product_id
             JOIN batches b ON b.id = sti.batch_id
             WHERE sti.stock_transfer_id = :id AND sti.deleted_at IS NULL
             ORDER BY p.name, b.expiry_date',
            [':id' => $id],
        );

        $from = Database::instance()->fetch('SELECT name FROM branches WHERE id = :id', [':id' => (int) $transfer->from_branch_id]);
        $to = Database::instance()->fetch('SELECT name FROM branches WHERE id = :id', [':id' => (int) $transfer->to_branch_id]);

        $this->view('inventory.transfer-show', [
            'title' => 'Transfer ' . $transfer->transfer_number,
            'pageTitle' => 'Transfer detail',
            'transfer' => $transfer,
            'lines' => $lines,
            'fromName' => $from['name'] ?? '—',
            'toName' => $to['name'] ?? '—',
        ]);
    }

    public function transferForm(Request $request): never
    {
        $branchId = $this->branchId();
        $branches = Database::instance()->fetchAll(
            'SELECT * FROM branches WHERE deleted_at IS NULL AND is_active = 1 AND id != :b ORDER BY name',
            [':b' => $branchId],
        );
        $products = Database::instance()->fetchAll(
            'SELECT id, name, sku FROM products WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name',
        );

        $this->view('inventory.transfer', [
            'title' => 'Stock transfer',
            'pageTitle' => 'Transfer stock',
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    public function transfer(Request $request): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot transfer stock.');
        }

        $data = $this->validate($request->all(), [
            'to_branch_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|numeric',
        ]);

        $from = $this->branchId();
        $to = (int) $data['to_branch_id'];
        if ($to === $from) {
            $this->backWithError('Choose a different destination branch.', '/inventory/transfers/create');
        }
        $qty = (float) $data['quantity'];

        try {
            $allocation = (new FefoService())->allocate((int) $data['product_id'], $from, $qty);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/inventory');
        }

        $stock = new StockService();

        $transfer = StockTransfer::create([
            'from_branch_id' => $from,
            'to_branch_id' => $to,
            'transfer_number' => NumberService::next('TRF-', 'stock_transfers', 'transfer_number'),
            'status' => 'completed',
        ]);

        foreach ($allocation as $row) {
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $data['product_id'],
                'batch_id' => $row['batch']->id,
                'quantity' => $row['quantity'],
            ]);
        }

        $stock->decreaseFromAllocation($allocation, (int) $data['product_id'], $from);

        foreach ($allocation as $row) {
            $batch = $row['batch'];
            Batch::create([
                'product_id' => $batch->product_id,
                'branch_id' => $to,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'manufacture_date' => $batch->manufacture_date,
                'quantity_received' => $row['quantity'],
                'quantity_remaining' => $row['quantity'],
                'unit_cost' => $batch->unit_cost,
                'supplier_id' => $batch->supplier_id,
            ]);
            $stock->syncLevelFromBatches((int) $data['product_id'], $to);
        }

        $this->backWithSuccess('Stock transferred.', '/inventory/transfers');
    }
}
