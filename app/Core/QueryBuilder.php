<?php

declare(strict_types=1);

namespace App\Core;

final class QueryBuilder
{
    private Model $model;
    /** @var list<array{0:string,1:string,2:mixed}> */
    private array $wheres = [];
    private array $order = [];
    private ?int $limit = null;
    private int $offset = 0;
    private bool $withTrashed = false;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [$column, (string) $operator, $value];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [$column, 'IS', null];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [$column, 'IS NOT', null];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->order[] = [$column, $direction];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function withTrashed(): self
    {
        $this->withTrashed = true;
        return $this;
    }

    public function first(): ?Model
    {
        $this->limit = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    /** @return list<Model> */
    public function get(): array
    {
        [$sql, $params] = $this->toSql();
        $rows = Database::instance()->fetchAll($sql, $params);
        $class = $this->model::class;

        return array_map(static fn(array $row): Model => new $class($row), $rows);
    }

    public function count(): int
    {
        [$whereSql, $params] = $this->whereSql();
        $sql = 'SELECT COUNT(*) AS c FROM `' . $this->model->getTable() . '`' . $whereSql;
        $row = Database::instance()->fetch($sql, $params);
        return (int) ($row['c'] ?? 0);
    }

    public function paginate(?int $perPage = null, int $page = 1): array
    {
        $perPage = max(1, $perPage ?? per_page());
        $page = max(1, $page);
        $total = $this->count();
        $pages = (int) max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;

        return [
            'data' => $this->get(),
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    private function toSql(): array
    {
        [$whereSql, $params] = $this->whereSql();
        $sql = 'SELECT * FROM `' . $this->model->getTable() . '`' . $whereSql;

        if ($this->order !== []) {
            $parts = [];
            foreach ($this->order as [$col, $dir]) {
                $parts[] = '`' . $col . '` ' . $dir;
            }
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return [$sql, $params];
    }

    private function whereSql(): array
    {
        $clauses = [];
        $params = [];
        $i = 0;

        $wheres = $this->wheres;
        if ($this->model->usesSoftDeletes() && !$this->withTrashed) {
            $wheres[] = ['deleted_at', 'IS', null];
        }

        foreach ($wheres as [$column, $operator, $value]) {
            if ($operator === 'IS' || $operator === 'IS NOT') {
                $clauses[] = "`{$column}` {$operator} NULL";
                continue;
            }

            if (strtoupper($operator) === 'IN' && is_array($value)) {
                $placeholders = [];
                foreach (array_values($value) as $item) {
                    $key = ':w' . $i++;
                    $placeholders[] = $key;
                    $params[$key] = $item;
                }
                $clauses[] = "`{$column}` IN (" . implode(',', $placeholders) . ')';
                continue;
            }

            $key = ':w' . $i++;
            $clauses[] = "`{$column}` {$operator} {$key}";
            $params[$key] = $value;
        }

        $sql = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$sql, $params];
    }
}
