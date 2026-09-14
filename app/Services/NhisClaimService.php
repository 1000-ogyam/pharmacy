<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NhisClaim;

final class NhisClaimService
{
    public function submit(int $prescriptionId, ?int $saleId, float $amount): NhisClaim
    {
        return NhisClaim::create([
            'prescription_id' => $prescriptionId,
            'sale_id' => $saleId,
            'claim_number' => 'NHIS-' . date('Ymd') . '-' . random_int(1000, 9999),
            'status' => 'submitted',
            'amount_claimed' => $amount,
            'amount_approved' => 0,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
