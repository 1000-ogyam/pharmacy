<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\Product;
use RuntimeException;

final class FefoService
{
    /**
     * Allocate sellable batches for a product at a branch using FEFO.
     *
     * @return list<array{batch:Batch,quantity:float}>
     */
    public function allocate(int $productId, int $branchId, float $quantity): array
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        $batches = Batch::sellableFor($productId, $branchId);
        $remaining = $quantity;
        $allocation = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            if (!$batch->isSellable()) {
                continue;
            }

            $take = min((float) $batch->quantity_remaining, $remaining);
            if ($take <= 0) {
                continue;
            }

            $allocation[] = ['batch' => $batch, 'quantity' => $take];
            $remaining -= $take;
        }

        if ($remaining > 0.0001) {
            $available = $quantity - $remaining;
            $name = Product::find($productId)?->name ?? ('product #' . $productId);
            throw new RuntimeException(
                'Insufficient sellable stock for ' . $name
                . '. Requested ' . format_qty($quantity)
                . ', available ' . format_qty($available) . '.'
            );
        }

        return $allocation;
    }
}
