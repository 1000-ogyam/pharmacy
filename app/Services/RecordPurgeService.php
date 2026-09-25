<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDOException;

final class RecordPurgeService
{
    public function purgeProduct(int $productId): void
    {
        $db = Database::instance();
        $db->beginTransaction();

        try {
            $saleItemIds = array_map(
                static fn(array $row): int => (int) $row['id'],
                $db->fetchAll('SELECT id FROM sale_items WHERE product_id = :pid', [':pid' => $productId]),
            );

            if ($saleItemIds !== []) {
                $in = implode(',', $saleItemIds);
                $db->execute("DELETE FROM return_items WHERE sale_item_id IN ({$in})");
            }

            $db->execute('DELETE FROM return_items WHERE product_id = :pid', [':pid' => $productId]);

            $saleIds = array_map(
                static fn(array $row): int => (int) $row['sale_id'],
                $db->fetchAll(
                    'SELECT DISTINCT sale_id FROM sale_items WHERE product_id = :pid',
                    [':pid' => $productId],
                ),
            );

            $db->execute('DELETE FROM sale_items WHERE product_id = :pid', [':pid' => $productId]);

            foreach ($saleIds as $saleId) {
                $this->refreshSaleTotals($saleId);
            }

            $db->execute(
                'DELETE grni FROM goods_received_note_items grni
                 INNER JOIN purchase_order_items poi ON poi.id = grni.purchase_order_item_id
                 WHERE poi.product_id = :pid',
                [':pid' => $productId],
            );

            foreach ([
                'goods_received_note_items',
                'purchase_order_items',
                'stock_transfer_items',
                'wholesale_order_items',
                'quotation_items',
                'prescription_items',
                'product_prices',
                'batches',
                'stock_levels',
                'product_units',
            ] as $table) {
                $db->execute(
                    'DELETE FROM `' . $table . '` WHERE product_id = :pid',
                    [':pid' => $productId],
                );
            }

            $db->execute('DELETE FROM products WHERE id = :id', [':id' => $productId]);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function purgeSale(int $saleId): void
    {
        $db = Database::instance();
        $db->beginTransaction();

        try {
            $db->execute(
                'DELETE ri FROM return_items ri
                 INNER JOIN returns r ON r.id = ri.return_id
                 WHERE r.sale_id = :id',
                [':id' => $saleId],
            );
            $db->execute('DELETE FROM returns WHERE sale_id = :id', [':id' => $saleId]);
            $db->execute(
                "DELETE FROM payments WHERE payable_type = 'sale' AND payable_id = :id",
                [':id' => $saleId],
            );
            $db->execute('UPDATE deliveries SET sale_id = NULL WHERE sale_id = :id', [':id' => $saleId]);
            $db->execute('UPDATE nhis_claims SET sale_id = NULL WHERE sale_id = :id', [':id' => $saleId]);
            $db->execute('UPDATE invoices SET sale_id = NULL WHERE sale_id = :id', [':id' => $saleId]);
            $db->execute('DELETE FROM sale_items WHERE sale_id = :id', [':id' => $saleId]);
            $db->execute('DELETE FROM sales WHERE id = :id', [':id' => $saleId]);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function purgeCustomer(int $customerId): void
    {
        $db = Database::instance();
        $saleIds = array_map(
            static fn(array $row): int => (int) $row['id'],
            $db->fetchAll('SELECT id FROM sales WHERE customer_id = :cid', [':cid' => $customerId]),
        );

        foreach ($saleIds as $saleId) {
            $this->purgeSale($saleId);
        }

        $db->beginTransaction();
        try {
            $db->execute('DELETE ri FROM return_items ri INNER JOIN returns r ON r.id = ri.return_id WHERE r.customer_id = :id', [':id' => $customerId]);
            $db->execute('DELETE FROM returns WHERE customer_id = :id', [':id' => $customerId]);
            $db->execute('DELETE FROM deliveries WHERE customer_id = :id', [':id' => $customerId]);

            $orderIds = array_map(
                static fn(array $row): int => (int) $row['id'],
                $db->fetchAll('SELECT id FROM wholesale_orders WHERE customer_id = :cid', [':cid' => $customerId]),
            );
            foreach ($orderIds as $orderId) {
                $this->purgeWholesaleOrder($orderId, false);
            }

            $quotationIds = array_map(
                static fn(array $row): int => (int) $row['id'],
                $db->fetchAll('SELECT id FROM quotations WHERE customer_id = :cid', [':cid' => $customerId]),
            );
            foreach ($quotationIds as $qid) {
                $this->purgeQuotation($qid, false);
            }

            $db->execute('DELETE FROM invoices WHERE customer_id = :id', [':id' => $customerId]);
            $db->execute(
                'DELETE nc FROM nhis_claims nc
                 INNER JOIN prescriptions p ON p.id = nc.prescription_id
                 WHERE p.customer_id = :id',
                [':id' => $customerId],
            );
            $db->execute('DELETE FROM prescriptions WHERE customer_id = :id', [':id' => $customerId]);
            $db->execute('UPDATE users SET customer_id = NULL WHERE customer_id = :id', [':id' => $customerId]);
            $db->execute('DELETE FROM customers WHERE id = :id', [':id' => $customerId]);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function purgeSupplier(int $supplierId): void
    {
        $db = Database::instance();
        $poIds = array_map(
            static fn(array $row): int => (int) $row['id'],
            $db->fetchAll('SELECT id FROM purchase_orders WHERE supplier_id = :sid', [':sid' => $supplierId]),
        );

        foreach ($poIds as $poId) {
            $this->purgePurchaseOrder($poId, false);
        }

        $db->beginTransaction();
        try {
            $db->execute('UPDATE batches SET supplier_id = NULL WHERE supplier_id = :id', [':id' => $supplierId]);
            $db->execute('UPDATE users SET supplier_id = NULL WHERE supplier_id = :id', [':id' => $supplierId]);
            $db->execute('DELETE FROM suppliers WHERE id = :id', [':id' => $supplierId]);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function purgePurchaseOrder(int $purchaseOrderId, bool $useOwnTransaction = true): void
    {
        $db = Database::instance();
        if ($useOwnTransaction) {
            $db->beginTransaction();
        }

        try {
            $grnIds = array_map(
                static fn(array $row): int => (int) $row['id'],
                $db->fetchAll('SELECT id FROM goods_received_notes WHERE purchase_order_id = :po', [':po' => $purchaseOrderId]),
            );

            if ($grnIds !== []) {
                $in = implode(',', $grnIds);
                $db->execute("DELETE FROM goods_received_note_items WHERE grn_id IN ({$in})");
                $db->execute("DELETE FROM goods_received_notes WHERE id IN ({$in})");
            }

            $db->execute('DELETE FROM purchase_order_items WHERE purchase_order_id = :id', [':id' => $purchaseOrderId]);
            $db->execute('DELETE FROM purchase_orders WHERE id = :id', [':id' => $purchaseOrderId]);

            if ($useOwnTransaction) {
                $db->commit();
            }
        } catch (PDOException $e) {
            if ($useOwnTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function purgeQuotation(int $quotationId, bool $useOwnTransaction = true): void
    {
        $db = Database::instance();
        if ($useOwnTransaction) {
            $db->beginTransaction();
        }

        try {
            $orderIds = array_map(
                static fn(array $row): int => (int) $row['id'],
                $db->fetchAll('SELECT id FROM wholesale_orders WHERE quotation_id = :qid', [':qid' => $quotationId]),
            );
            foreach ($orderIds as $orderId) {
                $this->purgeWholesaleOrder($orderId, false);
            }

            $db->execute('DELETE FROM quotation_items WHERE quotation_id = :id', [':id' => $quotationId]);
            $db->execute('DELETE FROM quotations WHERE id = :id', [':id' => $quotationId]);

            if ($useOwnTransaction) {
                $db->commit();
            }
        } catch (PDOException $e) {
            if ($useOwnTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function purgeWholesaleOrder(int $orderId, bool $useOwnTransaction = true): void
    {
        $db = Database::instance();
        if ($useOwnTransaction) {
            $db->beginTransaction();
        }

        try {
            $db->execute('UPDATE deliveries SET wholesale_order_id = NULL WHERE wholesale_order_id = :id', [':id' => $orderId]);
            $db->execute('DELETE FROM wholesale_order_items WHERE wholesale_order_id = :id', [':id' => $orderId]);
            $db->execute('DELETE FROM invoices WHERE wholesale_order_id = :id', [':id' => $orderId]);
            $db->execute('DELETE FROM wholesale_orders WHERE id = :id', [':id' => $orderId]);

            if ($useOwnTransaction) {
                $db->commit();
            }
        } catch (PDOException $e) {
            if ($useOwnTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function refreshSaleTotals(int $saleId): void
    {
        $db = Database::instance();
        $sale = $db->fetch('SELECT discount, tax FROM sales WHERE id = :id', [':id' => $saleId]);
        if ($sale === null) {
            return;
        }

        $subRow = $db->fetch(
            'SELECT COALESCE(SUM(line_total), 0) AS subtotal FROM sale_items WHERE sale_id = :id',
            [':id' => $saleId],
        );
        $subtotal = (float) ($subRow['subtotal'] ?? 0);
        $discount = (float) $sale['discount'];
        $tax = (float) $sale['tax'];
        $total = max(0, $subtotal - $discount + $tax);

        $db->execute(
            'UPDATE sales SET subtotal = :subtotal, total = :total WHERE id = :id',
            [':subtotal' => $subtotal, ':total' => $total, ':id' => $saleId],
        );
    }
}
