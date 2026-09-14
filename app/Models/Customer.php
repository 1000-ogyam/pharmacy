<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected string $table = 'customers';

    public function availableCredit(): float
    {
        return (float) $this->credit_limit - (float) $this->credit_balance;
    }
}
