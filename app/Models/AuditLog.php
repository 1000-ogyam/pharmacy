<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected string $table = 'audit_log';
    protected bool $softDeletes = false;
    protected bool $audited = false;
    protected bool $timestamps = false;
}
