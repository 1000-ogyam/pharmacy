<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Model;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\Supplier;
use PDOException;

final class ArchiveController extends Controller
{
    /** @var array<string, array{label: string, class: class-string<Model>}> */
    private const TYPES = [
        'sales' => ['label' => 'Sales', 'class' => Sale::class],
        'products' => ['label' => 'Products', 'class' => Product::class],
        'customers' => ['label' => 'Customers', 'class' => Customer::class],
        'suppliers' => ['label' => 'Suppliers', 'class' => Supplier::class],
        'purchase-orders' => ['label' => 'Purchase orders', 'class' => PurchaseOrder::class],
        'quotations' => ['label' => 'Quotations', 'class' => Quotation::class],
    ];

    public function index(Request $request): never
    {
        $type = $this->normalizeType((string) $request->query('type', 'sales'));
        $q = trim((string) $request->query('q', ''));

        $result = $this->paginateArchived($type, $q);

        $this->view('archives.index', [
            'title' => 'Archives',
            'pageTitle' => 'Archives',
            'type' => $type,
            'types' => self::TYPES,
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'q' => $q,
            'canManage' => auth()->hasRole('admin'),
        ]);
    }

    public function restore(Request $request, string $type, int $id): never
    {
        $this->assertAdminOnly();

        $record = $this->findArchived($this->normalizeType($type), $id);
        $record->restore();

        $this->backWithSuccess('Record restored.', '/archives?type=' . urlencode($this->normalizeType($type)));
    }

    public function destroy(Request $request, string $type, int $id): never
    {
        $this->assertAdminOnly();

        $type = $this->normalizeType($type);
        $record = $this->findArchived($type, $id);

        try {
            $record->forceDelete();
        } catch (PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                $this->backWithError(
                    'This record cannot be permanently deleted because other data still references it.',
                    '/archives?type=' . urlencode($type),
                );
            }
            throw $e;
        }

        $this->backWithSuccess('Record permanently deleted.', '/archives?type=' . urlencode($type));
    }

    private function normalizeType(string $type): string
    {
        return array_key_exists($type, self::TYPES) ? $type : 'sales';
    }

    /** @return array{data: list<array<string, mixed>>, page: int, pages: int} */
    private function paginateArchived(string $type, string $q): array
    {
        return match ($type) {
            'sales' => $this->paginateSales($q),
            'products' => $this->paginateSimple('products', 'name', 'sku', $q, false),
            'customers' => $this->paginateSimple('customers', 'name', 'phone', $q, true),
            'suppliers' => $this->paginateSimple('suppliers', 'name', 'phone', $q, false),
            'purchase-orders' => $this->paginatePurchaseOrders($q),
            'quotations' => $this->paginateQuotations($q),
            default => ['data' => [], 'page' => 1, 'pages' => 1],
        };
    }

    /** @return array{data: list<array<string, mixed>>, page: int, pages: int} */
    private function paginateSales(string $q): array
    {
        $branchId = $this->branchId();
        $sql = 'SELECT s.id, s.sale_number AS label, s.total, s.status, s.deleted_at, s.created_at,
                       u.name AS extra
                FROM sales s
                JOIN users u ON u.id = s.user_id
                WHERE s.deleted_at IS NOT NULL AND s.branch_id = :branch_id';
        $params = [':branch_id' => $branchId];

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(['s.sale_number', 'u.name'], $q, 'arch_sale');
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY s.deleted_at DESC, s.id DESC';

        $result = Database::instance()->paginate($sql, $params);
        return ['data' => $result['data'], 'page' => $result['page'], 'pages' => $result['pages']];
    }

    /** @return array{data: list<array<string, mixed>>, page: int, pages: int} */
    private function paginateQuotations(string $q): array
    {
        $branchId = $this->branchId();
        $sql = 'SELECT q.id, q.quotation_number AS label, c.name AS extra, q.deleted_at, q.created_at,
                       q.total AS total
                FROM quotations q
                JOIN customers c ON c.id = q.customer_id
                WHERE q.deleted_at IS NOT NULL AND q.branch_id = :branch_id';
        $params = [':branch_id' => $branchId];

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(['q.quotation_number', 'c.name'], $q, 'arch_quote');
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY q.deleted_at DESC, q.id DESC';

        $result = Database::instance()->paginate($sql, $params);
        return ['data' => $result['data'], 'page' => $result['page'], 'pages' => $result['pages']];
    }

    /** @return array{data: list<array<string, mixed>>, page: int, pages: int} */
    private function paginatePurchaseOrders(string $q): array
    {
        $branchId = $this->branchId();
        $sql = 'SELECT po.id, po.po_number AS label, po.status AS extra, po.deleted_at, po.created_at,
                       po.total_ghs AS total
                FROM purchase_orders po
                WHERE po.deleted_at IS NOT NULL AND po.branch_id = :branch_id';
        $params = [':branch_id' => $branchId];

        if ($q !== '') {
            $sql .= ' AND po.po_number LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY po.deleted_at DESC, po.id DESC';

        $result = Database::instance()->paginate($sql, $params);
        return ['data' => $result['data'], 'page' => $result['page'], 'pages' => $result['pages']];
    }

    /** @return array{data: list<array<string, mixed>>, page: int, pages: int} */
    private function paginateSimple(string $table, string $labelCol, string $extraCol, string $q, bool $branchScoped): array
    {
        $sql = "SELECT id, {$labelCol} AS label, {$extraCol} AS extra, deleted_at, created_at
                FROM `{$table}`
                WHERE deleted_at IS NOT NULL";
        $params = [];

        if ($branchScoped) {
            $sql .= ' AND branch_id = :branch_id';
            $params[':branch_id'] = $this->branchId();
        }

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or([$labelCol, $extraCol], $q, 'arch_row');
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY deleted_at DESC, id DESC';

        $result = Database::instance()->paginate($sql, $params);
        return ['data' => $result['data'], 'page' => $result['page'], 'pages' => $result['pages']];
    }

    private function findArchived(string $type, int $id): Model
    {
        $class = self::TYPES[$type]['class'];
        $record = $class::query()->withTrashed()->where('id', $id)->first();

        if (!$record instanceof Model) {
            abort(404, 'Archived record not found.');
        }

        if ($record->deleted_at === null) {
            abort(404, 'This record is not archived.');
        }

        $this->assertArchiveAccess($type, $record);

        return $record;
    }

    private function assertArchiveAccess(string $type, Model $record): void
    {
        if (in_array($type, ['sales', 'customers', 'purchase-orders', 'quotations'], true)) {
            $this->assertSameBranch($record);
        }
    }

    private function assertAdminOnly(): void
    {
        if (!auth()->hasRole('admin')) {
            abort(403, 'Only administrators can restore or permanently delete archived records.');
        }
    }

    private function isForeignKeyViolation(PDOException $e): bool
    {
        $code = (string) $e->getCode();
        if ($code === '23000') {
            return str_contains(strtolower($e->getMessage()), 'foreign key')
                || str_contains($e->getMessage(), '1451');
        }

        return str_contains(strtolower($e->getMessage()), 'foreign key')
            || str_contains($e->getMessage(), '1451');
    }
}
