<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ReturnItem extends Model
{
    protected string $table = 'return_items';
    protected bool $audited = false;
}
