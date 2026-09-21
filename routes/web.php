<?php

declare(strict_types=1);

use App\Controllers\AccountingController;
use App\Controllers\ApprovalController;
use App\Controllers\AuthController;
use App\Controllers\BatchController;
use App\Controllers\CreditController;
use App\Controllers\CustomerController;
use App\Controllers\CustomerPortalController;
use App\Controllers\DashboardController;
use App\Controllers\DeliveryController;
use App\Controllers\InventoryController;
use App\Controllers\NhisController;
use App\Controllers\PosController;
use App\Controllers\PrescriptionController;
use App\Controllers\ProductController;
use App\Controllers\PurchaseOrderController;
use App\Controllers\ReportController;
use App\Controllers\ReturnController;
use App\Controllers\SmsController;
use App\Controllers\StaffController;
use App\Controllers\SupplierController;
use App\Controllers\SupplierPortalController;
use App\Controllers\UssdController;
use App\Controllers\HelpController;
use App\Controllers\PwaController;
use App\Controllers\WholesaleController;

/** @var \App\Core\Router $router */

$router->get('/', [AuthController::class, 'showLogin'])->middleware(['guest']);
$router->get('/login', [AuthController::class, 'showLogin'])->middleware(['guest']);
$router->post('/login', [AuthController::class, 'login'])->middleware(['guest', 'csrf']);
$router->get('/logout', [AuthController::class, 'logoutRedirect'])->middleware(['auth']);
$router->post('/logout', [AuthController::class, 'logout'])->middleware(['auth', 'csrf']);

$router->post('/ussd/webhook', [UssdController::class, 'webhook']);
$router->get('/help', [HelpController::class, 'index']);
$router->get('/manifest.webmanifest', [PwaController::class, 'manifest']);

$staff = 'admin,cashier,pharmacist,warehouse,finance,wholesale';

$router->group(['middleware' => ['auth', 'csrf']], function ($router) use ($staff) {
    $router->get('/dashboard', [DashboardController::class, 'index'])->middleware(['role:' . $staff]);

    $router->get('/pos', [PosController::class, 'index'])->middleware(['role:admin,cashier,pharmacist']);
    $router->get('/pos/search', [PosController::class, 'search'])->middleware(['role:admin,cashier,pharmacist']);
    $router->post('/pos/sale', [PosController::class, 'checkout'])->middleware(['role:admin,cashier,pharmacist']);
    $router->get('/pos/receipt/{id}', [PosController::class, 'receipt'])->middleware(['role:admin,cashier,pharmacist,finance']);

    $router->resource('/products', ProductController::class)->middleware(['role:admin,warehouse,wholesale']);
    $router->get('/inventory', [InventoryController::class, 'index'])->middleware(['role:admin,warehouse,pharmacist']);
    $router->get('/inventory/batches', [BatchController::class, 'index'])->middleware(['role:admin,warehouse,pharmacist']);
    $router->post('/inventory/batches/{id}/recall', [BatchController::class, 'recall'])->middleware(['role:admin,warehouse']);
    $router->get('/inventory/transfers/create', [InventoryController::class, 'transferForm'])->middleware(['role:admin,warehouse']);
    $router->post('/inventory/transfers', [InventoryController::class, 'transfer'])->middleware(['role:admin,warehouse']);

    $router->resource('/customers', CustomerController::class)->middleware(['role:admin,cashier,pharmacist,wholesale,finance']);
    $router->resource('/suppliers', SupplierController::class)->middleware(['role:admin,warehouse,finance']);
    $router->resource('/purchase-orders', PurchaseOrderController::class)->middleware(['role:admin,warehouse']);
    $router->post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive'])->middleware(['role:admin,warehouse']);

    $router->get('/wholesale', [WholesaleController::class, 'index'])->middleware(['role:admin,wholesale']);
    $router->get('/wholesale/quotations/create', [WholesaleController::class, 'createQuote'])->middleware(['role:admin,wholesale']);
    $router->post('/wholesale/quotations', [WholesaleController::class, 'storeQuote'])->middleware(['role:admin,wholesale']);
    $router->post('/wholesale/quotations/{id}/convert', [WholesaleController::class, 'convert'])->middleware(['role:admin,wholesale']);

    $router->get('/prescriptions', [PrescriptionController::class, 'index'])->middleware(['role:admin,pharmacist']);
    $router->get('/prescriptions/create', [PrescriptionController::class, 'create'])->middleware(['role:admin,pharmacist']);
    $router->post('/prescriptions', [PrescriptionController::class, 'store'])->middleware(['role:admin,pharmacist']);
    $router->get('/prescriptions/{id}', [PrescriptionController::class, 'show'])->middleware(['role:admin,pharmacist']);
    $router->post('/prescriptions/{id}/dispense', [PrescriptionController::class, 'dispense'])->middleware(['role:admin,pharmacist']);

    $router->get('/credit', [CreditController::class, 'index'])->middleware(['role:admin,finance,wholesale']);
    $router->post('/credit/{id}/collect', [CreditController::class, 'collect'])->middleware(['role:admin,finance']);

    $router->get('/accounting', [AccountingController::class, 'index'])->middleware(['role:admin,finance']);
    $router->get('/reports', [ReportController::class, 'index'])->middleware(['role:' . $staff]);

    $router->get('/deliveries', [DeliveryController::class, 'index'])->middleware(['role:admin,wholesale']);
    $router->post('/deliveries', [DeliveryController::class, 'store'])->middleware(['role:admin,wholesale']);
    $router->post('/deliveries/{id}/complete', [DeliveryController::class, 'complete'])->middleware(['role:admin,wholesale']);

    $router->get('/returns', [ReturnController::class, 'index'])->middleware(['role:admin,warehouse,pharmacist']);
    $router->post('/returns', [ReturnController::class, 'store'])->middleware(['role:admin,warehouse,pharmacist']);

    $router->get('/staff', [StaffController::class, 'index'])->middleware(['role:admin']);
    $router->get('/staff/create', [StaffController::class, 'create'])->middleware(['role:admin']);
    $router->post('/staff', [StaffController::class, 'store'])->middleware(['role:admin']);
    $router->get('/staff/{id}/edit', [StaffController::class, 'edit'])->middleware(['role:admin']);
    $router->put('/staff/{id}', [StaffController::class, 'update'])->middleware(['role:admin']);
    $router->patch('/staff/{id}', [StaffController::class, 'update'])->middleware(['role:admin']);
    $router->delete('/staff/{id}', [StaffController::class, 'destroy'])->middleware(['role:admin']);

    $router->get('/approvals', [ApprovalController::class, 'index'])->middleware(['role:admin,finance']);
    $router->post('/approvals/{id}', [ApprovalController::class, 'decide'])->middleware(['role:admin,finance']);

    $router->get('/sms', [SmsController::class, 'index'])->middleware(['role:admin']);
    $router->post('/sms', [SmsController::class, 'send'])->middleware(['role:admin']);
    $router->post('/sms/process', [SmsController::class, 'process'])->middleware(['role:admin']);

    $router->get('/nhis', [NhisController::class, 'index'])->middleware(['role:admin,pharmacist,finance']);
    $router->post('/nhis', [NhisController::class, 'submit'])->middleware(['role:admin,pharmacist']);

    $router->get('/portal', [CustomerPortalController::class, 'index'])->middleware(['role:admin,customer']);
    $router->get('/portal/orders', [CustomerPortalController::class, 'orders'])->middleware(['role:admin,customer']);
    $router->get('/portal/invoices', [CustomerPortalController::class, 'invoices'])->middleware(['role:admin,customer']);
    $router->get('/supplier-portal', [SupplierPortalController::class, 'index'])->middleware(['role:admin,supplier']);
});
