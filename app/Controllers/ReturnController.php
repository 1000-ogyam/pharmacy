<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Services\NumberService;
use App\Services\StockService;

final class ReturnController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT r.*, c.name AS customer_name
             FROM returns r
             LEFT JOIN customers c ON c.id = r.customer_id
             WHERE r.deleted_at IS NULL AND r.branch_id = :b
             ORDER BY r.id DESC',
            [':b' => $this->branchId()]
        );
        $sales = Sale::query()->where('branch_id', $this->branchId())->orderBy('id', 'DESC')->limit(50)->get();

        $this->view('returns.index', [
            'title' => 'Returns',
            'pageTitle' => 'Returns & recalls',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'sales' => $sales,
        ]);
    }

    public function store(Request $request): never
    {
        $sale = Sale::findOrFail((int) $request->input('sale_id'));
        $this->assertSameBranch($sale);
        $items = Database::instance()->fetchAll(
            'SELECT * FROM sale_items WHERE sale_id = :id AND deleted_at IS NULL',
            [':id' => $sale->id]
        );

        $ret = ProductReturn::create([
            'branch_id' => $sale->branch_id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'return_number' => NumberService::next('RTN-', 'returns', 'return_number'),
            'reason' => $request->input('reason', 'Customer return'),
            'status' => 'approved',
            'total' => $sale->total,
        ]);

        $stock = new StockService();
        foreach ($items as $item) {
            ReturnItem::create([
                'return_id' => $ret->id,
                'sale_item_id' => $item['id'],
                'product_id' => $item['product_id'],
                'batch_id' => $item['batch_id'],
                'quantity' => $item['quantity'],
            ]);
            $stock->increase((int) $item['product_id'], (int) $sale->branch_id, (int) $item['batch_id'], (float) $item['quantity']);
        }

        $this->backWithSuccess('Return captured and stock restored.', '/returns');
    }
}
