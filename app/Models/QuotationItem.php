<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class QuotationItem extends Model
{
    protected string $table = 'quotation_items';
    protected bool $audited = false;
}
