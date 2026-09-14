<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;

final class GraInvoiceService
{
    public function register(Invoice $invoice): Invoice
    {
        $irn = 'GRA-' . date('YmdHis') . '-' . $invoice->id;
        $invoice->update([
            'gra_irn' => $irn,
            'status' => $invoice->status === 'draft' ? 'issued' : $invoice->status,
        ]);

        return $invoice;
    }
}
