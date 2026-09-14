<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SmsLog extends Model
{
    protected string $table = 'sms_log';
    protected bool $audited = false;
}
