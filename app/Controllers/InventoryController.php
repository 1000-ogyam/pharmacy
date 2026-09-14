<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
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
        $branchId = (int) (auth()->branchId() ?? 0);
        $result = Database::instance()->paginate(
            'SELECT p.id, p.sku, p.name, p.category, sl.quantity, sl.reorder_level
             FROM products p
             LEFT JOIN stock_levels sl ON sl.product_id = p.id AND sl.branch_id = :b AND sl.deleted_at IS NULL
             WHERE p.deleted_at IS NULL
             ORDER BY sl.quantity ASC, p.name ASC',
            [':b' => $branchId]
        );

        $this->view('inventory.index', [
            'title' => 'Inventory',
            'pageTitle' => 'Stock levels',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function transferForm(Request $request): never
    {
        $branches = Database::instance()->fetchAll('SELECT * FROM branches WHERE deleted_at IS NULL AND is_active = 1');
        $products = Database::instance()->fetchAll('SELECT id, name, sku FROM products WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name');

        $this->view('inventory.transfer', [
            'title' => 'Stock transfer',
            'pageTitle' => 'Transfer stock',
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    public function transfer(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'to_branch_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|numeric',
        ]);

        $from = (int) (auth()->branchId() ?? 0);
        $to = (int) $data['to_branch_id'];
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
            $new = \App\Models\Batch::create([
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
            $stock->adjustLevel((int) $data['product_id'], $to, (float) $row['quantity']);
            unset($new);
        }

        $this->backWithSuccess('Stock transferred.', '/inventory');
    }
}
