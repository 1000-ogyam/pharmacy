<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Payment;

final class CreditController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT * FROM customers WHERE deleted_at IS NULL AND branch_id = :b AND (credit_limit > 0 OR credit_balance > 0) ORDER BY credit_balance DESC',
            [':b' => $this->branchId()]
        );

        $this->view('credit.index', [
            'title' => 'Credit',
            'pageTitle' => 'Credit accounts',
            'customers' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function collect(Request $request, int $id): never
    {
        $customer = Customer::findOrFail($id);
        $this->assertSameBranch($customer);
        $amount = (float) $request->input('amount', 0);

        if ($amount <= 0) {
            $this->backWithError('Enter a valid amount.');
        }

        $customer->update([
            'credit_balance' => max(0, (float) $customer->credit_balance - $amount),
        ]);

        Payment::create([
            'branch_id' => auth()->branchId(),
            'payable_type' => 'customer',
            'payable_id' => $customer->id,
            'method' => $request->input('method', 'cash'),
            'amount' => $amount,
            'reference' => 'CR-' . $customer->id,
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $this->backWithSuccess('Payment applied to customer credit.', '/credit');
    }
}
