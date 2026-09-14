<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class LedgerAccount extends Model
{
    protected string $table = 'ledger_accounts';
    protected bool $audited = false;
}
