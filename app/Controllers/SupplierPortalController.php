<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class SupplierPortalController extends Controller
{
    public function index(Request $request): never
    {
        $sql = 'SELECT po.*, s.name AS supplier_name
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             WHERE po.deleted_at IS NULL';
        $params = [];

        if (auth()->hasRole('supplier')) {
            $supplierId = auth()->supplierId();
            if ($supplierId === null) {
                $this->view('portal.supplier', [
                    'title' => 'Supplier portal',
                    'pageTitle' => 'Open purchase orders',
                    'orders' => [],
                    'page' => 1,
                    'pages' => 1,
                ], 'portal-supplier');
            }
            $sql .= ' AND po.supplier_id = :sid';
            $params[':sid'] = $supplierId;
        }

        $sql .= ' ORDER BY po.id DESC';
        $result = Database::instance()->paginate($sql, $params);

        $this->view('portal.supplier', [
            'title' => 'Supplier portal',
            'pageTitle' => 'Open purchase orders',
            'orders' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ], 'portal-supplier');
    }
}
