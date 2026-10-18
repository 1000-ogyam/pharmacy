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

        $this->syncLevelFromBatches($productId, $branchId);
    }

    public function increase(int $productId, int $branchId, int $batchId, float $quantity): void
    {
        $batch = Batch::findOrFail($batchId);
        $batch->update([
            'quantity_remaining' => (float) $batch->quantity_remaining + $quantity,
        ]);
        $this->syncLevelFromBatches($productId, $branchId);
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

    /** Total quantity on all batches at the branch (includes non-sellable). */
    public function onHandQuantity(int $productId, int $branchId): float
    {
        $row = Database::instance()->fetch(
            'SELECT COALESCE(SUM(quantity_remaining), 0) AS qty
             FROM batches
             WHERE product_id = :product_id
               AND branch_id = :branch_id
               AND deleted_at IS NULL',
            [':product_id' => $productId, ':branch_id' => $branchId],
        );

        return (float) ($row['qty'] ?? 0);
    }

    public function syncLevelFromBatches(int $productId, int $branchId): void
    {
        $onHand = round($this->onHandQuantity($productId, $branchId), 4);
        $level = StockLevel::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$level instanceof StockLevel) {
            if ($onHand <= 0) {
                return;
            }

            StockLevel::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity' => $onHand,
                'reorder_level' => 10,
            ]);

            return;
        }

        $level->update(['quantity' => max(0, $onHand)]);
    }

    public function adjustBranchStock(int $productId, int $branchId, string $mode, float $amount, string $sku = ''): void
    {
        $amount = max(0, round($amount, 4));
        if ($amount <= 0) {
            throw new RuntimeException('Enter a quantity greater than zero.');
        }

        $mode = strtolower(trim($mode));
        if ($mode === 'add') {
            $this->addToAdjustBatch($productId, $branchId, $amount, $sku);
        } elseif ($mode === 'remove') {
            $sellable = $this->available($productId, $branchId);
            if ($amount > $sellable + 0.0001) {
                throw new RuntimeException(
                    'Cannot remove more than sellable stock (' . format_qty($sellable) . ' available).'
                );
            }
            $this->decreaseFromSellableBatches($productId, $branchId, $amount);
        } else {
            throw new RuntimeException('Choose whether to add or remove stock.');
        }

        $this->syncLevelFromBatches($productId, $branchId);
    }

    public function setBranchQuantity(int $productId, int $branchId, float $targetQuantity, string $sku = ''): void
    {
        $targetQuantity = max(0, round($targetQuantity, 4));
        $current = round($this->onHandQuantity($productId, $branchId), 4);
        $delta = round($targetQuantity - $current, 4);

        if (abs($delta) < 0.0001) {
            $this->syncLevelFromBatches($productId, $branchId);

            return;
        }

        if ($delta > 0) {
            $this->addToAdjustBatch($productId, $branchId, $delta, $sku);
        } else {
            $this->decreaseFromSellableBatches($productId, $branchId, abs($delta));
        }

        $this->syncLevelFromBatches($productId, $branchId);
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
