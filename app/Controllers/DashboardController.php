<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class DashboardController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $db = Database::instance();

        $todaySales = $db->fetch(
            'SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS cnt
             FROM sales WHERE branch_id = :b AND deleted_at IS NULL AND DATE(created_at) = CURDATE()',
            [':b' => $branchId]
        );

        $monthSales = $db->fetch(
            'SELECT COALESCE(SUM(total),0) AS total
             FROM sales WHERE branch_id = :b AND deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")',
            [':b' => $branchId]
        );

        $lowStock = $db->fetch(
            'SELECT COUNT(*) AS cnt FROM stock_levels
             WHERE branch_id = :b AND deleted_at IS NULL AND quantity <= reorder_level',
            [':b' => $branchId]
        );

        $expiring = $db->fetch(
            'SELECT COUNT(*) AS cnt FROM batches
             WHERE branch_id = :b AND deleted_at IS NULL AND is_recalled = 0
               AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
               AND quantity_remaining > 0',
            [':b' => $branchId]
        );

        $recentSales = $db->paginate(
            'SELECT s.*, c.name AS customer_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.branch_id = :b AND s.deleted_at IS NULL
             ORDER BY s.id DESC',
            [':b' => $branchId],
            page_number()
        );

        $alerts = $db->paginate(
            'SELECT p.name, b.batch_number, b.expiry_date, b.quantity_remaining
             FROM batches b
             JOIN products p ON p.id = b.product_id
             WHERE b.branch_id = :b AND b.deleted_at IS NULL AND b.quantity_remaining > 0
               AND (b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) OR b.is_recalled = 1)
             ORDER BY b.expiry_date ASC',
            [':b' => $branchId],
            page_number('apage')
        );

        $this->view('dashboard.index', [
            'title' => 'Dashboard',
            'pageTitle' => 'Dashboard',
            'todaySales' => $todaySales,
            'monthSales' => $monthSales,
            'lowStock' => $lowStock,
            'expiring' => $expiring,
            'recentSales' => $recentSales['data'],
            'page' => $recentSales['page'],
            'pages' => $recentSales['pages'],
            'alerts' => $alerts['data'],
            'alertPage' => $alerts['page'],
            'alertPages' => $alerts['pages'],
        ]);
    }
}
