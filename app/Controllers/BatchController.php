<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;

final class BatchController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $result = Database::instance()->paginate(
            'SELECT b.*, p.name AS product_name, p.sku
             FROM batches b
             JOIN products p ON p.id = b.product_id
             WHERE b.deleted_at IS NULL AND b.branch_id = :b
             ORDER BY b.expiry_date ASC',
            [':b' => $branchId]
        );

        $this->view('inventory.batches', [
            'title' => 'Batches',
            'pageTitle' => 'Batch & expiry control',
            'batches' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function recall(Request $request, int $id): never
    {
        $batch = Batch::findOrFail($id);
        $this->assertSameBranch($batch);
        $batch->update([
            'is_recalled' => 1,
            'recall_reason' => $request->input('recall_reason', 'Recalled'),
        ]);
        $this->backWithSuccess('Batch recalled and blocked from sale.', '/inventory/batches');
    }
}
