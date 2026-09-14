<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Services\NumberService;
use App\Services\SaleService;

final class PrescriptionController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate(
            'SELECT rx.*, c.name AS customer_name
             FROM prescriptions rx
             JOIN customers c ON c.id = rx.customer_id
             WHERE rx.deleted_at IS NULL AND rx.branch_id = :b
             ORDER BY rx.id DESC',
            [':b' => $this->branchId()]
        );

        $this->view('prescriptions.index', [
            'title' => 'Prescriptions',
            'pageTitle' => 'Prescriptions',
            'rows' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
        ]);
    }

    public function create(Request $request): never
    {
        $this->view('prescriptions.form', [
            'title' => 'New prescription',
            'pageTitle' => 'New prescription',
            'customers' => Customer::query()->where('branch_id', $this->branchId())->orderBy('name')->get(),
            'products' => Product::where('is_active', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validate($request->all(), ['customer_id' => 'required|integer']);
        $customer = Customer::findOrFail((int) $data['customer_id']);
        $this->assertSameBranch($customer);
        $rx = Prescription::create([
            'branch_id' => $this->branchId(),
            'customer_id' => $data['customer_id'],
            'prescribed_by' => $request->input('prescribed_by'),
            'prescription_number' => NumberService::next('RX-', 'prescriptions', 'prescription_number'),
            'diagnosis' => $request->input('diagnosis'),
            'status' => 'pending',
        ]);

        $productIds = (array) $request->input('product_id', []);
        $qtys = (array) $request->input('quantity', []);
        $doses = (array) $request->input('dosage', []);

        foreach ($productIds as $i => $pid) {
            if (!$pid) {
                continue;
            }
            PrescriptionItem::create([
                'prescription_id' => $rx->id,
                'product_id' => $pid,
                'dosage' => $doses[$i] ?? '',
                'quantity' => $qtys[$i] ?? 1,
                'instructions' => $request->input('instructions'),
            ]);
        }

        $this->backWithSuccess('Prescription captured.', '/prescriptions');
    }

    public function show(Request $request, int $id): never
    {
        $rx = Prescription::findOrFail($id);
        $this->assertSameBranch($rx);
        $items = Database::instance()->fetchAll(
            'SELECT ri.*, p.name AS product_name
             FROM prescription_items ri
             JOIN products p ON p.id = ri.product_id
             WHERE ri.prescription_id = :id AND ri.deleted_at IS NULL',
            [':id' => $id]
        );

        $this->view('prescriptions.show', [
            'title' => $rx->prescription_number,
            'pageTitle' => $rx->prescription_number,
            'rx' => $rx,
            'items' => $items,
        ]);
    }

    public function dispense(Request $request, int $id): never
    {
        $rx = Prescription::findOrFail($id);
        $this->assertSameBranch($rx);
        $items = PrescriptionItem::where('prescription_id', $id)->get();
        $cart = [];

        foreach ($items as $item) {
            $cart[] = ['product_id' => $item->product_id, 'quantity' => $item->quantity];
            $item->update(['quantity_dispensed' => $item->quantity]);
        }

        $sale = (new SaleService())->checkout([
            'items' => $cart,
            'customer_id' => $rx->customer_id,
            'payment_method' => $request->input('payment_method', 'cash'),
            'sale_type' => 'retail',
            'notes' => 'Dispensed from ' . $rx->prescription_number,
        ]);

        $rx->update([
            'status' => 'dispensed',
            'dispensed_by' => auth()->id(),
            'dispensed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->backWithSuccess('Prescription dispensed as ' . $sale->sale_number . '.', '/prescriptions');
    }
}
