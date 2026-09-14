<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SmsQueue extends Model
{
    protected string $table = 'sms_queue';
    protected bool $audited = false;
    protected bool $softDeletes = false;
}
