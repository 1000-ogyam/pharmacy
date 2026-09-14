<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashbookEntry;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;

final class LedgerService
{
    public function post(int $branchId, string $accountCode, float $debit, float $credit, string $description, string $refType, int $refId): LedgerEntry
    {
        $account = LedgerAccount::where('code', $accountCode)->first();
        if (!$account instanceof LedgerAccount) {
            $account = LedgerAccount::create([
                'code' => $accountCode,
                'name' => $accountCode,
                'type' => 'asset',
            ]);
        }

        return LedgerEntry::create([
            'branch_id' => $branchId,
            'account_id' => $account->id,
            'entry_date' => date('Y-m-d'),
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'reference_type' => $refType,
            'reference_id' => $refId,
        ]);
    }

    public function cashIn(int $branchId, float $amount, string $method, string $description, string $reference): CashbookEntry
    {
        return CashbookEntry::create([
            'branch_id' => $branchId,
            'entry_date' => date('Y-m-d'),
            'type' => 'in',
            'method' => $method,
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
        ]);
    }

    public function cashOut(int $branchId, float $amount, string $method, string $description, string $reference): CashbookEntry
    {
        return CashbookEntry::create([
            'branch_id' => $branchId,
            'entry_date' => date('Y-m-d'),
            'type' => 'out',
            'method' => $method,
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
        ]);
    }
}
