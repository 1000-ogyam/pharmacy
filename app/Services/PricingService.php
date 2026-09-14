<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Customer;

final class PricingService
{
    public function unitPrice(int $productId, int $branchId, ?Customer $customer = null, ?int $unitId = null): float
    {
        $priceType = 'retail';

        if ($customer && $customer->type === 'wholesale') {
            $priceType = (string) ($customer->pricing_tier ?: 'wholesale_tier1');
        }

        $db = Database::instance();

        $sql = 'SELECT price FROM product_prices
                WHERE product_id = :product_id
                  AND price_type = :price_type
                  AND deleted_at IS NULL
                  AND (branch_id = :branch_id OR branch_id IS NULL)';

        $params = [
            ':product_id' => $productId,
            ':price_type' => $priceType,
            ':branch_id' => $branchId,
        ];

        if ($unitId !== null) {
            $sql .= ' AND (unit_id = :unit_id OR unit_id IS NULL)';
            $params[':unit_id'] = $unitId;
        }

        $sql .= ' ORDER BY branch_id DESC, unit_id DESC LIMIT 1';

        $row = $db->fetch($sql, $params);

        if ($row) {
            return (float) $row['price'];
        }

        if ($priceType !== 'retail') {
            return $this->unitPrice($productId, $branchId, null, $unitId);
        }

        return 0.0;
    }
}
