<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Sale;
use App\Services\ReturnService;
use RuntimeException;

final class ReturnController extends Controller
{
    public function index(Request $request): never
    {
        $sq = trim((string) $request->query('sq', ''));
        $preselectSaleId = (int) $request->query('sale_id', 0);

        $result = Database::instance()->paginate(
            'SELECT r.*, c.name AS customer_name, s.sale_number
             FROM returns r
             LEFT JOIN customers c ON c.id = r.customer_id
             LEFT JOIN sales s ON s.id = r.sale_id
             WHERE r.deleted_at IS NULL AND r.branch_id = :b
             ORDER BY r.id DESC',
            [':b' => $this->branchId()],
        );

        $this->view('returns.index', [
            'title' => 'Returns',
            'pageTitle' => 'Returns & recalls',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'sales' => $this->returnableSales($sq),
            'sq' => $sq,
            'preselectSaleId' => $preselectSaleId,
        ]);
    }

    public function store(Request $request): never
    {
        if (!can_mutate_data()) {
            abort(403, 'You cannot create returns.');
        }

        $sale = Sale::findOrFail((int) $request->input('sale_id'));
        $this->assertSameBranch($sale);

        $reason = trim((string) $request->input('reason', 'Customer return'));

        try {
            (new ReturnService())->processFullReturn($sale, $reason);
        } catch (RuntimeException $e) {
            $this->backWithError($e->getMessage(), '/returns');
        }

        $this->backWithSuccess('Return recorded. Stock restored and sale totals reversed.', '/returns');
    }

    /** @return list<Sale> */
    private function returnableSales(string $q): array
    {
        $sql = 'SELECT s.*
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                WHERE s.deleted_at IS NULL
                  AND s.branch_id = :b
                  AND s.status = :status
                  AND NOT EXISTS (
                      SELECT 1 FROM returns r
                      WHERE r.sale_id = s.id AND r.deleted_at IS NULL
                  )';
        $params = [':b' => $this->branchId(), ':status' => 'completed'];

        if ($q !== '') {
            [$likeSql, $likeParams] = sql_like_or(
                ['s.sale_number', 'c.name'],
                $q,
                'ret_sq',
            );
            $sql .= ' AND ' . $likeSql;
            $params = [...$params, ...$likeParams];
        }

        $sql .= ' ORDER BY s.created_at DESC, s.id DESC LIMIT 100';

        $rows = Database::instance()->fetchAll($sql, $params);

        return array_map(static fn(array $row): Sale => new Sale($row), $rows);
    }
}
