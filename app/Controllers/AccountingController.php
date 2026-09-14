<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class AccountingController extends Controller
{
    public function index(Request $request): never
    {
        $branchId = (int) (auth()->branchId() ?? 0);
        $db = Database::instance();

        $cash = $db->fetch(
            'SELECT
                COALESCE(SUM(CASE WHEN type = "in" THEN amount ELSE 0 END),0) AS cash_in,
                COALESCE(SUM(CASE WHEN type = "out" THEN amount ELSE 0 END),0) AS cash_out
             FROM cashbook_entries WHERE branch_id = :b AND deleted_at IS NULL',
            [':b' => $branchId]
        );

        $ar = $db->fetch(
            'SELECT COALESCE(SUM(credit_balance),0) AS ar FROM customers WHERE deleted_at IS NULL'
        );

        $entries = $db->paginate(
            'SELECT le.*, la.code, la.name AS account_name
             FROM ledger_entries le
             JOIN ledger_accounts la ON la.id = le.account_id
             WHERE le.branch_id = :b AND le.deleted_at IS NULL
             ORDER BY le.id DESC',
            [':b' => $branchId]
        );

        $income = $db->fetch(
            'SELECT COALESCE(SUM(le.credit),0) AS income
             FROM ledger_entries le
             JOIN ledger_accounts la ON la.id = le.account_id
             WHERE le.branch_id = :b AND la.type = "income" AND le.deleted_at IS NULL',
            [':b' => $branchId]
        );

        $expense = $db->fetch(
            'SELECT COALESCE(SUM(le.debit),0) AS expense
             FROM ledger_entries le
             JOIN ledger_accounts la ON la.id = le.account_id
             WHERE le.branch_id = :b AND la.type = "expense" AND le.deleted_at IS NULL',
            [':b' => $branchId]
        );

        $this->view('accounting.index', [
            'title' => 'Accounting',
            'pageTitle' => 'Accounting',
            'cash' => $cash,
            'ar' => $ar,
            'entries' => $entries['data'],
            'page' => $entries['page'],
            'pages' => $entries['pages'],
            'income' => $income,
            'expense' => $expense,
        ]);
    }
}
