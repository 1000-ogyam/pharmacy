<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WholesaleOrderItem extends Model
{
    protected string $table = 'wholesale_order_items';
    protected bool $audited = false;
}
