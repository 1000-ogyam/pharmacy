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

    public function levelQuantity(int $productId, int $branchId): float
    {
        $row = Database::instance()->fetch(
            'SELECT quantity FROM stock_levels
             WHERE product_id = :pid AND branch_id = :bid AND deleted_at IS NULL',
            [':pid' => $productId, ':bid' => $branchId],
        );

        return (float) ($row['quantity'] ?? 0);
    }

    public function setBranchQuantity(int $productId, int $branchId, float $targetQuantity, string $sku = ''): void
    {
        $targetQuantity = max(0, round($targetQuantity, 4));
        $current = $this->levelQuantity($productId, $branchId);
        $delta = round($targetQuantity - $current, 4);

        if (abs($delta) < 0.0001) {
            return;
        }

        if ($delta > 0) {
            $this->addToAdjustBatch($productId, $branchId, $delta, $sku);
            $this->adjustLevel($productId, $branchId, $delta);
            return;
        }

        $this->decreaseFromSellableBatches($productId, $branchId, abs($delta));
        $this->adjustLevel($productId, $branchId, $delta);
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

    private function addToAdjustBatch(int $productId, int $branchId, float $quantity, string $sku): void
    {
        $batches = Batch::sellableFor($productId, $branchId);
        foreach ($batches as $batch) {
            if (str_starts_with((string) $batch->batch_number, 'ADJ-') || str_starts_with((string) $batch->batch_number, 'OPEN-')) {
                $batch->update([
                    'quantity_received' => (float) $batch->quantity_received + $quantity,
                    'quantity_remaining' => (float) $batch->quantity_remaining + $quantity,
                ]);

                return;
            }
        }

        $suffix = $sku !== '' ? strtoupper(preg_replace('/[^A-Z0-9-]/', '', $sku) ?? 'SKU') : 'SKU';
        Batch::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'batch_number' => 'ADJ-' . $suffix . '-' . date('ymdHis'),
            'expiry_date' => date('Y-m-d', strtotime('+24 months')),
            'manufacture_date' => date('Y-m-d'),
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost' => 0,
        ]);
    }

    private function decreaseFromSellableBatches(int $productId, int $branchId, float $quantity): void
    {
        $remaining = $quantity;
        $batches = Batch::sellableFor($productId, $branchId);

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $onHand = (float) $batch->quantity_remaining;
            $take = min($onHand, $remaining);
            if ($take <= 0) {
                continue;
            }

            $batch->update(['quantity_remaining' => max(0, $onHand - $take)]);
            $remaining -= $take;
        }

        if ($remaining > 0.0001) {
            throw new RuntimeException('Not enough sellable batch stock to reduce quantity by that amount.');
        }
    }
}
