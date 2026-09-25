<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\RecordPurgeService;

class Supplier extends Model
{
    protected string $table = 'suppliers';

    public function forceDelete(): bool
    {
        $id = (int) ($this->attributes[$this->primaryKey] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $before = $this->attributes;
        (new RecordPurgeService())->purgeSupplier($id);

        if ($this->audited) {
            $this->afterSave('delete', $before, ['id' => $id]);
        }

        return true;
    }
}
