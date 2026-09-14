<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Batch;
use App\Models\StockLevel;
use RuntimeException;

final class StockService
{
    public function decreaseFromAllocation(array $allocation, int $productId, int $branchId): void
    {
        foreach ($allocation as $row) {
            /** @var Batch $batch */
            $batch = $row['batch'];
            $qty = (float) $row['quantity'];

            if (!$batch->isSellable()) {
                throw new RuntimeException('Batch ' . $batch->batch_number . ' is not sellable.');
            }

            $newQty = (float) $batch->quantity_remaining - $qty;
            if ($newQty < -0.0001) {
                throw new RuntimeException('Batch quantity would go negative.');
            }

            $batch->update(['quantity_remaining' => max(0, $newQty)]);
        }

        $this->adjustLevel($productId, $branchId, -1 * $this->allocationTotal($allocation));
    }

    public function increase(int $productId, int $branchId, int $batchId, float $quantity): void
    {
        $batch = Batch::findOrFail($batchId);
        $batch->update([
            'quantity_remaining' => (float) $batch->quantity_remaining + $quantity,
        ]);
        $this->adjustLevel($productId, $branchId, $quantity);
    }

    public function adjustLevel(int $productId, int $branchId, float $delta): void
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$level instanceof StockLevel) {
            StockLevel::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity' => max(0, $delta),
                'reorder_level' => 10,
            ]);
            return;
        }

        $level->update([
            'quantity' => max(0, (float) $level->quantity + $delta),
        ]);
    }

    public function available(int $productId, int $branchId): float
    {
        $row = Database::instance()->fetch(
            'SELECT COALESCE(SUM(quantity_remaining), 0) AS qty
             FROM batches
             WHERE product_id = :product_id
               AND branch_id = :branch_id
               AND deleted_at IS NULL
               AND is_recalled = 0
               AND expiry_date > CURDATE()
               AND quantity_remaining > 0',
            [':product_id' => $productId, ':branch_id' => $branchId]
        );

        return (float) ($row['qty'] ?? 0);
    }

    private function allocationTotal(array $allocation): float
    {
        $total = 0.0;
        foreach ($allocation as $row) {
            $total += (float) $row['quantity'];
        }
        return $total;
    }
}
