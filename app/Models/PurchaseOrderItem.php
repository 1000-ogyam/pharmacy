<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PurchaseOrderItem extends Model
{
    protected string $table = 'purchase_order_items';
    protected bool $audited = false;
}
