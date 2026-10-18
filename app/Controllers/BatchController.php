<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;
use App\Models\Product;
use App\Services\StockService;

final class BatchController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = $this->branchId();
        $productId = (int) $request->query('product_id', 0);
        $params = [':b' => $branchId];
        $productFilter = '';
        if ($productId > 0) {
            $productFilter = ' AND b.product_id = :pid';
            $params[':pid'] = $productId;
        }

        $result = Database::instance()->paginate(
            'SELECT b.*, p.name AS product_name, p.sku
             FROM batches b
             JOIN products p ON p.id = b.product_id
             WHERE b.deleted_at IS NULL AND b.branch_id = :b' . $productFilter . '
             ORDER BY b.expiry_date ASC',
            $params,
        );

        $filterProduct = null;
        if ($productId > 0) {
            $filterProduct = Product::find($productId);
        }

        $this->view('inventory.batches', [
            'title' => 'Batches',
            'pageTitle' => 'Batch & expiry control',
            'batches' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filterProductId' => $productId > 0 ? $productId : null,
            'filterProduct' => $filterProduct,
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('inventory.batch-form', [
            'title' => 'New batch',
            'pageTitle' => 'Receive batch',
            'batch' => null,
            'products' => $this->activeProducts(),
            'suppliers' => $this->activeSuppliers(),
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireMutate();

        $data = $this->validate($request->all(), [
            'product_id' => 'required|integer',
            'batch_number' => 'required',
            'expiry_date' => 'required',
            'quantity' => 'required|numeric',
            'unit_cost' => 'numeric',
        ]);

        $qty = (float) $data['quantity'];
        if ($qty <= 0) {
            $this->backWithError('Enter a quantity greater than zero.', '/inventory/batches/create');
        }

        $product = Product::findOrFail((int) $data['product_id']);
        $branchId = $this->branchId();
        $batchNumber = trim((string) $data['batch_number']);

        if ($this->batchNumberExists($branchId, (int) $product->id, $batchNumber, null)) {
            $this->backWithError('This batch number already exists for that product at your branch.', '/inventory/batches/create');
        }

        $supplierId = (int) $request->input('supplier_id', 0);
        Batch::create([
            'product_id' => (int) $product->id,
            'branch_id' => $branchId,
            'batch_number' => $batchNumber,
            'expiry_date' => (string) $data['expiry_date'],
            'manufacture_date' => $request->input('manufacture_date') ?: null,
            'quantity_received' => $qty,
            'quantity_remaining' => $qty,
            'unit_cost' => (float) ($data['unit_cost'] ?? 0),
            'supplier_id' => $supplierId > 0 ? $supplierId : null,
        ]);

        (new StockService())->syncLevelFromBatches((int) $product->id, $branchId);

        $this->backWithSuccess('Batch ' . $batchNumber . ' added for ' . $product->name . '.', '/inventory/batches');
    }

    public function show(Request $request, int $id): never
    {
        $batch = $this->findBranchBatch($id);
        $product = Product::findOrFail((int) $batch->product_id);
        $supplierName = null;
        if ($batch->supplier_id) {
            $row = Database::instance()->fetch(
                'SELECT name FROM suppliers WHERE id = :id AND deleted_at IS NULL',
                [':id' => (int) $batch->supplier_id],
            );
            $supplierName = $row['name'] ?? null;
        }

        $usage = $this->batchUsageCount($id);

        $this->view('inventory.batch-show', [
            'title' => 'Batch ' . $batch->batch_number,
            'pageTitle' => 'Batch detail',
            'batch' => $batch,
            'product' => $product,
            'supplierName' => $supplierName,
            'usageCount' => $usage,
        ]);
    }

    public function edit(Request $request, int $id): never
    {
        $batch = $this->findBranchBatch($id);

        $this->view('inventory.batch-form', [
            'title' => 'Edit batch',
            'pageTitle' => 'Edit batch',
            'batch' => $batch,
            'products' => $this->activeProducts(),
            'suppliers' => $this->activeSuppliers(),
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $this->requireMutate();

        $batch = $this->findBranchBatch($id);
        $data = $this->validate($request->all(), [
            'batch_number' => 'required',
            'expiry_date' => 'required',
            'quantity_remaining' => 'required|numeric',
            'unit_cost' => 'numeric',
        ]);

        $remaining = (float) $data['quantity_remaining'];
        if ($remaining < 0) {
            $this->backWithError('Remaining quantity cannot be negative.', '/inventory/batches/' . $id . '/edit');
        }

        $received = (float) $request->input('quantity_received', $batch->quantity_received);
        if ($remaining > $received + 0.0001) {
            $received = $remaining;
        }

        $batchNumber = trim((string) $data['batch_number']);
        if ($this->batchNumberExists((int) $batch->branch_id, (int) $batch->product_id, $batchNumber, (int) $batch->id)) {
            $this->backWithError('This batch number already exists for that product at your branch.', '/inventory/batches/' . $id . '/edit');
        }

        $consumed = max(0, (float) $batch->quantity_received - (float) $batch->quantity_remaining);
        $floor = max(0, $received - $consumed);
        if ($remaining + 0.0001 < $floor) {
            $this->backWithError(
                'Remaining quantity cannot be less than ' . format_qty($floor) . ' (already used on sales or transfers).',
                '/inventory/batches/' . $id . '/edit',
            );
        }

        $batch->update([
            'batch_number' => $batchNumber,
            'expiry_date' => (string) $data['expiry_date'],
            'manufacture_date' => $request->input('manufacture_date') ?: null,
            'quantity_received' => $received,
            'quantity_remaining' => $remaining,
            'unit_cost' => (float) ($data['unit_cost'] ?? 0),
            'supplier_id' => ($sid = (int) $request->input('supplier_id', 0)) > 0 ? $sid : null,
        ]);

        (new StockService())->syncLevelFromBatches((int) $batch->product_id, (int) $batch->branch_id);

        $this->backWithSuccess('Batch updated.', '/inventory/batches/' . $id);
    }

    public function destroy(Request $request, int $id): never
    {
        if (!can_delete_data()) {
            abort(403, 'You cannot delete batch records.');
        }

        $batch = $this->findBranchBatch($id);
        if ($this->batchUsageCount($id) > 0) {
            $this->backWithError('This batch is linked to sales or transfers and cannot be deleted.', '/inventory/batches/' . $id);
        }

        if ((float) $batch->quantity_remaining > 0.0001) {
            $this->backWithError('Remove or transfer remaining quantity before deleting this batch.', '/inventory/batches/' . $id);
        }

        $productId = (int) $batch->product_id;
        $branchId = (int) $batch->branch_id;
        $batch->delete();
        (new StockService())->syncLevelFromBatches($productId, $branchId);

        $this->backWithSuccess('Batch deleted.', '/inventory/batches');
    }

    public function recall(Request $request, int $id): never
    {
        $this->requireMutate();

        $batch = $this->findBranchBatch($id);
        $batch->update([
            'is_recalled' => 1,
            'recall_reason' => $request->input('recall_reason', 'Recalled'),
        ]);
        $this->backWithSuccess('Batch recalled and blocked from sale.', '/inventory/batches');
    }

    private function findBranchBatch(int $id): Batch
    {
        $batch = Batch::findOrFail($id);
        $this->assertSameBranch($batch);

        return $batch;
    }

    private function requireMutate(): void
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot change inventory records.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function activeProducts(): array
    {
        return Database::instance()->fetchAll(
            'SELECT id, name, sku FROM products WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name',
        );
    }

    /** @return list<array<string, mixed>> */
    private function activeSuppliers(): array
    {
        return Database::instance()->fetchAll(
            'SELECT id, name FROM suppliers WHERE deleted_at IS NULL ORDER BY name',
        );
    }

    private function batchNumberExists(int $branchId, int $productId, string $batchNumber, ?int $exceptId): bool
    {
        $sql = 'SELECT id FROM batches
                WHERE branch_id = :b AND product_id = :p AND batch_number = :n AND deleted_at IS NULL';
        $params = [':b' => $branchId, ':p' => $productId, ':n' => $batchNumber];
        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $exceptId;
        }

        return Database::instance()->fetch($sql, $params) !== null;
    }

    private function batchUsageCount(int $batchId): int
    {
        $sales = Database::instance()->fetch(
            'SELECT COUNT(*) AS c FROM sale_items WHERE batch_id = :id',
            [':id' => $batchId],
        );
        $transfers = Database::instance()->fetch(
            'SELECT COUNT(*) AS c FROM stock_transfer_items WHERE batch_id = :id AND deleted_at IS NULL',
            [':id' => $batchId],
        );
        $returns = Database::instance()->fetch(
            'SELECT COUNT(*) AS c FROM return_items WHERE batch_id = :id',
            [':id' => $batchId],
        );
        $wholesale = Database::instance()->fetch(
            'SELECT COUNT(*) AS c FROM wholesale_order_items WHERE batch_id = :id',
            [':id' => $batchId],
        );

        return (int) ($sales['c'] ?? 0)
            + (int) ($transfers['c'] ?? 0)
            + (int) ($returns['c'] ?? 0)
            + (int) ($wholesale['c'] ?? 0);
    }
}
