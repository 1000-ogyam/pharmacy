<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Sale extends Model
{
    protected string $table = 'sales';

    public function items(): array
    {
        return SaleItem::where('sale_id', (int) $this->id)->get();
    }
}
