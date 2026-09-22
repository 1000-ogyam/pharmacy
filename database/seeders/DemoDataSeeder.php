<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Core\Database;
use App\Models\Approval;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LedgerAccount;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\SmsTemplate;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SaleService;

final class DemoDataSeeder
{
    public function run(): void
    {
        $db = Database::instance();
        $existing = $db->fetch('SELECT COUNT(*) AS c FROM users');
        if ((int) ($existing['c'] ?? 0) > 0) {
            echo "Users already exist — skipping seed.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');

        $roles = [
            ['Admin', 'admin'],
            ['Manager', 'manager'],
            ['Cashier', 'cashier'],
            ['Pharmacist', 'pharmacist'],
            ['Warehouse', 'warehouse'],
            ['Finance', 'finance'],
            ['Wholesale', 'wholesale'],
            ['Customer', 'customer'],
            ['Supplier', 'supplier'],
        ];
        $roleIds = [];
        foreach ($roles as [$name, $slug]) {
            $role = Role::create(['name' => $name, 'slug' => $slug, 'description' => $name]);
            $roleIds[$slug] = (int) $role->id;
        }

        $accra = Branch::create([
            'name' => 'Accra Main',
            'code' => 'ACC',
            'address' => 'Ring Road Central',
            'city' => 'Accra',
            'phone' => '0302555000',
            'email' => 'accra@plpharma.com',
            'is_active' => 1,
        ]);
        $kumasi = Branch::create([
            'name' => 'Kumasi Wholesale',
            'code' => 'KSI',
            'address' => 'Adum',
            'city' => 'Kumasi',
            'phone' => '0322022000',
            'email' => 'kumasi@plpharma.com',
            'is_active' => 1,
        ]);

        $password = password_hash('Password123!', PASSWORD_DEFAULT);
        $users = [
            ['Admin User', 'admin@plpharma.com', 'admin'],
            ['Mensah Manager', 'manager@plpharma.com', 'manager'],
            ['Ama Cashier', 'cashier@plpharma.com', 'cashier'],
            ['Kojo Pharmacist', 'pharmacist@plpharma.com', 'pharmacist'],
            ['Yaw Warehouse', 'warehouse@plpharma.com', 'warehouse'],
            ['Efua Finance', 'finance@plpharma.com', 'finance'],
            ['Kofi Wholesale', 'wholesale@plpharma.com', 'wholesale'],
        ];
        foreach ($users as [$name, $email, $slug]) {
            User::create([
                'branch_id' => $accra->id,
                'role_id' => $roleIds[$slug],
                'name' => $name,
                'email' => $email,
                'phone' => '0244000000',
                'password' => $password,
                'is_active' => 1,
                'licence_number' => $slug === 'pharmacist' ? 'PC-GH-10422' : null,
                'licence_expires_at' => $slug === 'pharmacist' ? date('Y-m-d', strtotime('+45 days')) : null,
            ]);
        }

        $accounts = [
            ['1000', 'Cash', 'asset'],
            ['1100', 'Accounts Receivable', 'asset'],
            ['1200', 'Inventory', 'asset'],
            ['2000', 'Accounts Payable', 'liability'],
            ['4000', 'Sales Revenue', 'income'],
            ['5000', 'Cost of Goods', 'expense'],
        ];
        foreach ($accounts as [$code, $name, $type]) {
            LedgerAccount::create(['code' => $code, 'name' => $name, 'type' => $type]);
        }

        $supplier = Supplier::create([
            'name' => 'Ernest Chemists Ltd',
            'contact_person' => 'Abena Mensah',
            'phone' => '0302666111',
            'email' => 'orders@ernestchemists.com',
            'address' => 'Accra',
            'currency_code' => 'GHS',
            'payment_terms_days' => 21,
            'is_active' => 1,
        ]);
        Supplier::create([
            'name' => 'Kinapharma',
            'contact_person' => 'Import Desk',
            'phone' => '0302777222',
            'email' => 'supply@kinapharma.com',
            'currency_code' => 'USD',
            'payment_terms_days' => 30,
            'is_active' => 1,
        ]);

        $retail = Customer::create([
            'branch_id' => $accra->id,
            'type' => 'retail',
            'name' => 'Akosua Boateng',
            'phone' => '0244111222',
            'email' => 'akosua@example.com',
            'nhis_number' => 'NHIS-882211',
            'credit_limit' => 0,
            'credit_balance' => 0,
            'sms_opt_in' => 1,
            'is_active' => 1,
        ]);
        $wholesaleCustomer = Customer::create([
            'branch_id' => $accra->id,
            'type' => 'wholesale',
            'name' => 'Hope Pharmacy Ltd',
            'phone' => '0208333444',
            'email' => 'hope@example.com',
            'credit_limit' => 20000,
            'credit_balance' => 1500,
            'pricing_tier' => 'wholesale_tier1',
            'sms_opt_in' => 1,
            'is_active' => 1,
        ]);

        $catalogue = [
            ['PCM500', 'Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'tablet', '500mg', 1.20, 0.85, 0],
            ['AMX500', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'capsule', '500mg', 2.50, 1.80, 1],
            ['ORS01', 'ORS Sachet', 'Oral rehydration salts', 'Electrolyte', 'sachet', '20.5g', 1.50, 0.90, 0],
            ['ALU24', 'A-L 20/120 (24s)', 'Artemether/Lumefantrine', 'Antimalarial', 'tablet', '20/120mg', 18.00, 12.00, 0],
            ['IBU400', 'Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'tablet', '400mg', 1.80, 1.10, 0],
            ['MET500', 'Metformin 500mg', 'Metformin', 'Antidiabetic', 'tablet', '500mg', 0.90, 0.55, 1],
            ['VITC', 'Vitamin C 1000mg', 'Ascorbic acid', 'Vitamin', 'tablet', '1000mg', 2.20, 1.40, 0],
            ['COUGH', 'Cough Syrup 100ml', 'Guaifenesin', 'Cough', 'syrup', '100ml', 14.00, 9.00, 0],
        ];

        $productIds = [];
        foreach ($catalogue as $row) {
            [$sku, $name, $generic, $cat, $form, $strength, $retailPrice, $wholesalePrice, $rx] = $row;
            $product = Product::create([
                'sku' => $sku,
                'barcode' => '890' . $sku,
                'name' => $name,
                'generic_name' => $generic,
                'category' => $cat,
                'dosage_form' => $form,
                'strength' => $strength,
                'manufacturer' => 'PL Pharma',
                'requires_prescription' => $rx,
                'is_controlled' => 0,
                'is_active' => 1,
            ]);
            $unit = ProductUnit::create([
                'product_id' => $product->id,
                'unit_name' => $form === 'tablet' || $form === 'capsule' ? 'tablet' : 'unit',
                'conversion_factor' => 1,
                'is_base' => 1,
            ]);
            ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'price_type' => 'retail', 'price' => $retailPrice]);
            ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'price_type' => 'wholesale_tier1', 'price' => $wholesalePrice]);
            $productIds[] = (int) $product->id;
        }

        foreach ($productIds as $i => $pid) {
            $qty = 80 + ($i * 15);
            Batch::create([
                'product_id' => $pid,
                'branch_id' => $accra->id,
                'batch_number' => 'B-ACC-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'expiry_date' => date('Y-m-d', strtotime('+' . (8 + $i) . ' months')),
                'manufacture_date' => date('Y-m-d', strtotime('-6 months')),
                'quantity_received' => $qty,
                'quantity_remaining' => $qty,
                'unit_cost' => 0.40 + $i * 0.1,
                'supplier_id' => $supplier->id,
            ]);
            StockLevel::create([
                'product_id' => $pid,
                'branch_id' => $accra->id,
                'quantity' => $qty,
                'reorder_level' => 20,
            ]);
        }

        Batch::create([
            'product_id' => $productIds[0],
            'branch_id' => $accra->id,
            'batch_number' => 'B-EXP-001',
            'expiry_date' => date('Y-m-d', strtotime('+20 days')),
            'quantity_received' => 12,
            'quantity_remaining' => 12,
            'unit_cost' => 0.35,
            'supplier_id' => $supplier->id,
        ]);
        $level = StockLevel::where('product_id', $productIds[0])->where('branch_id', (int) $accra->id)->first();
        if ($level instanceof StockLevel) {
            $level->update(['quantity' => (float) $level->quantity + 12]);
        }

        Batch::create([
            'product_id' => $productIds[1],
            'branch_id' => $accra->id,
            'batch_number' => 'B-DEAD-001',
            'expiry_date' => date('Y-m-d', strtotime('-10 days')),
            'quantity_received' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 0.50,
            'supplier_id' => $supplier->id,
            'is_recalled' => 0,
        ]);

        $templates = [
            ['receipt', 'Hello {{customer_name}}, receipt {{invoice_no}} for {{amount}} at PL Pharma. Thank you.'],
            ['invoice', 'Invoice {{invoice_no}} totalling {{amount}} is ready.'],
            ['expiry_alert', 'Batch alert: {{product_name}} batch {{batch_number}} expires {{expiry_date}}.'],
            ['otp', 'Your PL PharmaCore OTP is {{otp}}.'],
        ];
        foreach ($templates as [$key, $body]) {
            SmsTemplate::create(['key' => $key, 'body' => $body, 'is_active' => 1]);
        }

        $po = PurchaseOrder::create([
            'branch_id' => $accra->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-SEED-0001',
            'status' => 'sent',
            'currency_code' => 'GHS',
            'exchange_rate_at_purchase' => 1,
            'subtotal' => 400,
            'tax' => 0,
            'total_foreign' => 400,
            'total_ghs' => 400,
            'expected_date' => date('Y-m-d', strtotime('+5 days')),
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $productIds[0],
            'quantity_ordered' => 200,
            'quantity_received' => 0,
            'unit_cost' => 0.40,
            'line_total' => 80,
        ]);

        Approval::create([
            'type' => 'credit_increase',
            'record_type' => 'customers',
            'record_id' => $wholesaleCustomer->id,
            'requested_by' => 1,
            'status' => 'pending',
            'notes' => 'Request to raise Hope Pharmacy limit to GHS 30,000',
        ]);

        $admin = User::where('email', 'admin@plpharma.com')->first();
        if ($admin instanceof User) {
            \App\Core\Auth::instance()->login($admin);
        }

        try {
            (new SaleService())->checkout([
                'items' => [
                    ['product_id' => $productIds[0], 'quantity' => 4],
                    ['product_id' => $productIds[3], 'quantity' => 1],
                ],
                'customer_id' => $retail->id,
                'payment_method' => 'cash',
                'sale_type' => 'retail',
            ]);
            (new SaleService())->checkout([
                'items' => [
                    ['product_id' => $productIds[2], 'quantity' => 6],
                ],
                'customer_id' => $retail->id,
                'payment_method' => 'mobile_money',
                'sale_type' => 'retail',
            ]);
        } catch (\Throwable $e) {
            echo 'Sample sales skipped: ' . $e->getMessage() . PHP_EOL;
        }

        unset($now, $kumasi);
    }
}
