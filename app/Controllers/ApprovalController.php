<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Approval;

final class ApprovalController extends Controller
{
    public function index(Request $request): never
    {
        $result = Approval::query()->orderBy('id', 'DESC')->paginate(per_page(), page_number());
        $this->view('approvals.index', [
            'title' => 'Approvals',
            'pageTitle' => 'Approvals',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function decide(Request $request, int $id): never
    {
        $approval = Approval::findOrFail($id);
        $status = (string) $request->input('status', 'approved');
        if (!in_array($status, ['approved', 'rejected'], true)) {
            $this->backWithError('Invalid approval decision.', '/approvals');
        }
        $approval->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);
        $this->backWithSuccess('Approval updated.', '/approvals');
    }
}
