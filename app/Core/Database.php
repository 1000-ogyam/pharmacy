<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $host = (string) config('database.host');
        $port = (int) config('database.port', 3306);
        $name = (string) config('database.name');
        $user = (string) config('database.user');
        $pass = (string) config('database.pass', '');
        $charset = (string) config('database.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function connection(): PDO
    {
        return self::instance()->pdo;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, per_page: int, page: int, pages: int}
     */
    public function paginate(string $sql, array $params = [], ?int $page = null, ?int $perPage = null): array
    {
        $page = max(1, $page ?? page_number());
        $perPage = max(1, $perPage ?? per_page());
        $countRow = $this->fetch('SELECT COUNT(*) AS c FROM (' . $sql . ') AS _paged', $params);
        $total = (int) ($countRow['c'] ?? 0);
        $pages = (int) max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $rows = $this->fetchAll($sql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);

        return [
            'data' => $rows,
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
