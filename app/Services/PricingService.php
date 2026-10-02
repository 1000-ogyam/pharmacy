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

        return $this->priceByType($productId, $branchId, $priceType, $unitId);
    }

    public function unitPriceForPos(
        int $productId,
        int $branchId,
        string $priceMode = 'retail',
        ?Customer $customer = null,
        ?int $unitId = null,
    ): float {
        if ($priceMode !== 'wholesale') {
            return $this->priceByType($productId, $branchId, 'retail', $unitId);
        }

        $priceType = 'wholesale_tier1';
        if ($customer instanceof Customer && $customer->type === 'wholesale') {
            $tier = trim((string) ($customer->pricing_tier ?? ''));
            if ($tier !== '') {
                $priceType = $tier;
            }
        }

        $wholesale = $this->priceByType($productId, $branchId, $priceType, $unitId);
        if ($wholesale > 0) {
            return $wholesale;
        }

        return $this->priceByType($productId, $branchId, 'retail', $unitId);
    }

    private function priceByType(int $productId, int $branchId, string $priceType, ?int $unitId = null): float
    {
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
            return $this->priceByType($productId, $branchId, 'retail', $unitId);
        }

        return 0.0;
    }
}
