<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class NumberService
{
    public static function next(string $prefix, string $table, string $column): string
    {
        $allowed = [
            'sales' => 'sale_number',
            'purchase_orders' => 'po_number',
            'goods_received_notes' => 'grn_number',
            'quotations' => 'quotation_number',
            'wholesale_orders' => 'order_number',
            'invoices' => 'invoice_number',
            'prescriptions' => 'prescription_number',
            'returns' => 'return_number',
            'stock_transfers' => 'transfer_number',
            'nhis_claims' => 'claim_number',
        ];

        if (!isset($allowed[$table]) || $allowed[$table] !== $column) {
            throw new RuntimeException('Invalid numbering target.');
        }

        $row = Database::instance()->fetch(
            "SELECT COUNT(*) AS c FROM `{$table}`"
        );

        $seq = (int) ($row['c'] ?? 0) + 1;
        return $prefix . date('ymd') . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
