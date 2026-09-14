<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $persistExcept = ['role_slug', 'role_name', 'branch_name'];

    public static function find(int|string $id): ?static
    {
        $row = Database::instance()->fetch(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, b.name AS branch_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.id = :id AND u.deleted_at IS NULL',
            [':id' => $id]
        );

        return $row ? new static($row) : null;
    }

    public static function where(string $column, mixed $operator, mixed $value = null): \App\Core\QueryBuilder
    {
        return parent::where($column, $operator, $value);
    }

    public function hydrateRole(): static
    {
        if ($this->role_slug) {
            return $this;
        }

        $row = Database::instance()->fetch(
            'SELECT r.slug AS role_slug, r.name AS role_name, b.name AS branch_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.id = :id',
            [':id' => $this->id]
        );

        if ($row) {
            $this->fill($row);
        }

        return $this;
    }
}
