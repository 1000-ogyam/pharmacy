<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PrescriptionItem extends Model
{
    protected string $table = 'prescription_items';
    protected bool $audited = false;
}
