<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\RecordPurgeService;

class Sale extends Model
{
    protected string $table = 'sales';

    public function items(): array
    {
        return SaleItem::where('sale_id', (int) $this->id)->get();
    }

    public function forceDelete(): bool
    {
        $id = (int) ($this->attributes[$this->primaryKey] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $before = $this->attributes;
        (new RecordPurgeService())->purgeSale($id);

        if ($this->audited) {
            $this->afterSave('delete', $before, ['id' => $id]);
        }

        return true;
    }
}
