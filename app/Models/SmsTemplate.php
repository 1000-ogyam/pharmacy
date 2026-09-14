<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SmsTemplate extends Model
{
    protected string $table = 'sms_templates';
    protected bool $audited = false;
}
