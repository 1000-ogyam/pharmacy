<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SaleItem extends Model
{
    protected string $table = 'sale_items';
    protected bool $audited = false;
}
