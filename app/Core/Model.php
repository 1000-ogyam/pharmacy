<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuditService;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDeletes = true;
    protected bool $audited = true;
    protected array $attributes = [];
    protected array $original = [];
    /** @var list<string> */
    protected array $persistExcept = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(new static());
    }

    public static function find(int|string $id): ?static
    {
        return static::query()->where((new static())->primaryKey, $id)->first();
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            abort(404, 'Record not found.');
        }
        return $model;
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function create(array $data): static
    {
        $model = new static();
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');
        $userId = auth()->id();

        if ($this->timestamps) {
            $this->attributes['updated_at'] = $now;
            if (!isset($this->attributes[$this->primaryKey]) || $this->attributes[$this->primaryKey] === null) {
                $this->attributes['created_at'] = $this->attributes['created_at'] ?? $now;
            }
        }

        if ($this->audited && $userId !== null) {
            if (!isset($this->attributes[$this->primaryKey])) {
                $this->attributes['created_by'] = $this->attributes['created_by'] ?? $userId;
            }
            $this->attributes['updated_by'] = $userId;
        }

        $isNew = empty($this->attributes[$this->primaryKey]);
        $before = $isNew ? null : $this->original;

        if ($isNew) {
            $this->insertRow();
        } else {
            $this->updateRow();
        }

        $this->afterSave($isNew ? 'create' : 'update', $before, $this->attributes);
        $this->original = $this->attributes;

        return true;
    }

    public function delete(): bool
    {
        if (empty($this->attributes[$this->primaryKey])) {
            return false;
        }

        if (!$this->softDeletes) {
            throw new \RuntimeException('Hard deletes are not permitted on ' . static::class . '.');
        }

        $before = $this->attributes;
        $now = date('Y-m-d H:i:s');
        $this->attributes['deleted_at'] = $now;

        if ($this->audited && auth()->id() !== null) {
            $this->attributes['updated_by'] = auth()->id();
        }

        if ($this->timestamps) {
            $this->attributes['updated_at'] = $now;
        }

        $this->updateRow();
        $this->afterSave('delete', $before, $this->attributes);
        $this->original = $this->attributes;

        return true;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function getTable(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        $class = (new \ReflectionClass($this))->getShortName();
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class) ?? $class);
        return $snake . 's';
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function afterSave(string $action, ?array $before, array $after): void
    {
        if (!$this->audited) {
            return;
        }

        AuditService::log(
            $this->getTable(),
            (int) ($after[$this->primaryKey] ?? 0),
            $action,
            $before,
            $after,
        );
    }

    private function persistable(): array
    {
        $data = $this->attributes;
        foreach ($this->persistExcept as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    private function insertRow(): void
    {
        $data = $this->persistable();
        unset($data[$this->primaryKey]);

        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $col): string => ':' . $col, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->getTable(),
            implode('`,`', $columns),
            implode(',', $placeholders),
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        Database::instance()->execute($sql, $params);
        $this->attributes[$this->primaryKey] = Database::instance()->lastInsertId();
    }

    private function updateRow(): void
    {
        $data = $this->persistable();
        $id = $data[$this->primaryKey];
        unset($data[$this->primaryKey]);

        $sets = [];
        $params = [':_pk' => $id];

        foreach ($data as $key => $value) {
            $sets[] = "`{$key}` = :{$key}";
            $params[':' . $key] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = :_pk',
            $this->getTable(),
            implode(', ', $sets),
            $this->primaryKey,
        );

        Database::instance()->execute($sql, $params);
    }
}
