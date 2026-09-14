<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class ReportController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $from = (string) $request->query('from', date('Y-m-01'));
        $to = (string) $request->query('to', date('Y-m-d'));
        $db = Database::instance();

        $params = [':b' => $branchId, ':from' => $from, ':to' => $to];
        $sales = $db->paginate(
            'SELECT DATE(created_at) AS d, COUNT(*) AS cnt, SUM(total) AS total
             FROM sales
             WHERE branch_id = :b AND deleted_at IS NULL AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY DATE(created_at)
             ORDER BY d DESC',
            $params,
            page_number()
        );

        $top = $db->paginate(
            'SELECT p.name, SUM(si.quantity) AS qty, SUM(si.line_total) AS total
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN products p ON p.id = si.product_id
             WHERE s.branch_id = :b AND s.deleted_at IS NULL AND DATE(s.created_at) BETWEEN :from AND :to
             GROUP BY p.id, p.name
             ORDER BY total DESC',
            $params,
            page_number('tpage')
        );

        $this->view('reports.index', [
            'title' => 'Reports',
            'pageTitle' => 'Reports',
            'sales' => $sales['data'],
            'page' => $sales['page'],
            'pages' => $sales['pages'],
            'top' => $top['data'],
            'topPage' => $top['page'],
            'topPages' => $top['pages'],
            'from' => $from,
            'to' => $to,
        ]);
    }
}
