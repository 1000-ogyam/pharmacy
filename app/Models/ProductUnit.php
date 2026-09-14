<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ProductUnit extends Model
{
    protected string $table = 'product_units';
    protected bool $audited = false;
}
