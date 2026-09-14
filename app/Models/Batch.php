<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Batch extends Model
{
    protected string $table = 'batches';

    public function isSellable(): bool
    {
        if ((int) $this->is_recalled === 1) {
            return false;
        }

        if ((float) $this->quantity_remaining <= 0) {
            return false;
        }

        if ($this->expiry_date && strtotime((string) $this->expiry_date) <= strtotime('today')) {
            return false;
        }

        return true;
    }

    /** @return list<self> */
    public static function sellableFor(int $productId, int $branchId): array
    {
        /** @var list<self> $rows */
        $rows = static::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('is_recalled', 0)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date', 'ASC')
            ->get();

        return array_values(array_filter($rows, static fn(self $batch): bool => $batch->isSellable()));
    }
}
