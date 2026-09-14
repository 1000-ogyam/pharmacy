<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Prescription;
use App\Services\NhisClaimService;

final class NhisController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT n.*, rx.prescription_number, c.name AS customer_name
             FROM nhis_claims n
             JOIN prescriptions rx ON rx.id = n.prescription_id
             JOIN customers c ON c.id = rx.customer_id
             WHERE n.deleted_at IS NULL AND rx.branch_id = :b
             ORDER BY n.id DESC',
            [':b' => $this->branchId()]
        );

        $this->view('nhis.index', [
            'title' => 'NHIS',
            'pageTitle' => 'NHIS claims',
            'claims' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'prescriptions' => Prescription::query()->where('branch_id', $this->branchId())->orderBy('id', 'DESC')->limit(50)->get(),
        ]);
    }

    public function submit(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'prescription_id' => 'required|integer',
            'amount' => 'required|numeric',
        ]);

        $rx = Prescription::findOrFail((int) $data['prescription_id']);
        $this->assertSameBranch($rx);
        (new NhisClaimService())->submit((int) $rx->id, null, (float) $data['amount']);
        $this->backWithSuccess('NHIS claim submitted.', '/nhis');
    }
}
