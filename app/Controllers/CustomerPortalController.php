<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class CustomerPortalController extends Controller
{
    public function index(Request $request): never
    {
        $this->renderPortal('Customer portal', 'Customer portal');
    }

    public function orders(Request $request): never
    {
        $this->renderPortal('My orders', 'My orders');
    }

    public function invoices(Request $request): never
    {
        $this->index($request);
    }

    private function renderPortal(string $title, string $pageTitle): never
    {
        $sql = 'SELECT * FROM invoices WHERE deleted_at IS NULL';
        $params = [];

        if (auth()->hasRole('customer')) {
            $customerId = auth()->customerId();
            if ($customerId === null) {
                $this->view('portal.customer', [
                    'title' => $title,
                    'pageTitle' => $pageTitle,
                    'invoices' => [],
                    'page' => 1,
                    'pages' => 1,
                ], 'portal-customer');
            }
            $sql .= ' AND customer_id = :cid';
            $params[':cid'] = $customerId;
        }

        $sql .= ' ORDER BY id DESC';
        $result = Database::instance()->paginate($sql, $params);

        $this->view('portal.customer', [
            'title' => $title,
            'pageTitle' => $pageTitle,
            'invoices' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ], 'portal-customer');
    }
}
