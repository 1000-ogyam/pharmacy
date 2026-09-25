<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use App\Services\RecordPurgeService;

class Product extends Model
{
    protected string $table = 'products';

    public function units(): array
    {
        return ProductUnit::where('product_id', (int) $this->id)->get();
    }

    public function stockAt(int $branchId): float
    {
        $row = Database::instance()->fetch(
            'SELECT quantity FROM stock_levels WHERE product_id = :pid AND branch_id = :bid AND deleted_at IS NULL',
            [':pid' => $this->id, ':bid' => $branchId]
        );

        return (float) ($row['quantity'] ?? 0);
    }

    public function forceDelete(): bool
    {
        $id = (int) ($this->attributes[$this->primaryKey] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $before = $this->attributes;
        (new RecordPurgeService())->purgeProduct($id);

        if ($this->audited) {
            $this->afterSave('delete', $before, ['id' => $id]);
        }

        return true;
    }
}
