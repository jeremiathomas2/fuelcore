<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\FuelSalesController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\NozzleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StationSwitchController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TankController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

/*
|--------------------------------------------------------------------------
| Guest: Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated: Application
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* ---------- Monitoring ---------- */
    Route::get('/live', [LiveController::class, 'index'])->name('live');
    Route::get('/live/data', [LiveController::class, 'data'])->name('live.data');

    /* ---------- Search ---------- */
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    /* ---------- Stations ---------- */
    Route::middleware('permission:station.view')->group(function () {
        Route::get('/stations', [StationController::class, 'index'])->name('stations.index');
        Route::get('/stations/{station}', [StationController::class, 'show'])->name('stations.show')->whereNumber('station');
    });
    Route::middleware('permission:station.manage')->group(function () {
        Route::get('/stations/create', [StationController::class, 'create'])->name('stations.create');
        Route::post('/stations', [StationController::class, 'store'])->name('stations.store');
        Route::get('/stations/{station}/edit', [StationController::class, 'edit'])->name('stations.edit')->whereNumber('station');
        Route::put('/stations/{station}', [StationController::class, 'update'])->name('stations.update')->whereNumber('station');
    });
    Route::post('/stations/switch', [StationSwitchController::class, 'update'])->name('stations.switch');

    /* ---------- Pumps & Nozzles ---------- */
    Route::middleware('permission:pump.view')->group(function () {
        Route::get('/pumps', [PumpController::class, 'index'])->name('pumps.index');
        Route::get('/nozzles', [NozzleController::class, 'index'])->name('nozzles.index');
    });
    Route::middleware('permission:pump.manage')->group(function () {
        Route::get('/pumps/create', [PumpController::class, 'create'])->name('pumps.create');
        Route::post('/pumps', [PumpController::class, 'store'])->name('pumps.store');
        Route::get('/pumps/{pump}/edit', [PumpController::class, 'edit'])->name('pumps.edit')->whereNumber('pump');
        Route::put('/pumps/{pump}', [PumpController::class, 'update'])->name('pumps.update')->whereNumber('pump');
        Route::get('/nozzles/create', [NozzleController::class, 'create'])->name('nozzles.create');
        Route::post('/nozzles', [NozzleController::class, 'store'])->name('nozzles.store');
        Route::get('/nozzles/{nozzle}/edit', [NozzleController::class, 'edit'])->name('nozzles.edit')->whereNumber('nozzle');
        Route::put('/nozzles/{nozzle}', [NozzleController::class, 'update'])->name('nozzles.update')->whereNumber('nozzle');
    });

    /* ---------- Products ---------- */
    Route::middleware('permission:product.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    });
    Route::middleware('permission:product.manage')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit')->whereNumber('product');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update')->whereNumber('product');
        Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggleStatus')->whereNumber('product');
    });

    /* ---------- Tanks ---------- */
    Route::middleware('permission:tank.view')->group(function () {
        Route::get('/tanks', [TankController::class, 'index'])->name('tanks.index');
    });
    Route::middleware('permission:tank.manage')->group(function () {
        Route::get('/tanks/create', [TankController::class, 'create'])->name('tanks.create');
        Route::post('/tanks', [TankController::class, 'store'])->name('tanks.store');
        Route::get('/tanks/{tank}/edit', [TankController::class, 'edit'])->name('tanks.edit')->whereNumber('tank');
        Route::put('/tanks/{tank}', [TankController::class, 'update'])->name('tanks.update')->whereNumber('tank');
        Route::post('/tanks/{tank}/readings', [TankController::class, 'storeReading'])->name('tanks.readings.store');
    });

    /* ---------- POS & Sales ---------- */
    Route::middleware('permission:pos.use')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos/complete', [PosController::class, 'store'])->name('pos.complete');
    });
    Route::middleware('permission:sale.view')->group(function () {
        Route::get('/sales', [FuelSalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/{transaction}', [FuelSalesController::class, 'show'])->name('sales.show')->whereNumber('transaction');
    });
    Route::post('/sales/{transaction}/void', [FuelSalesController::class, 'void'])
        ->name('sales.void')
        ->middleware('permission:sale.void');

    /* ---------- Payments ---------- */
    Route::middleware('permission:payment.view')->get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::middleware('permission:payment.manage')->group(function () {
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    });

    /* ---------- Customers & Fleet ---------- */
    Route::middleware('permission:customer.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show')->whereNumber('customer');
    });
    Route::middleware('permission:customer.manage')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit')->whereNumber('customer');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')->whereNumber('customer');
        Route::patch('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus')->whereNumber('customer');
    });
    Route::middleware('permission:fleet.view')->group(function () {
        Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
        Route::get('/fleet/{fleet}', [FleetController::class, 'show'])->name('fleet.show')->whereNumber('fleet');
    });
    Route::middleware('permission:fleet.manage')->group(function () {
        Route::get('/fleet/create', [FleetController::class, 'create'])->name('fleet.create');
        Route::post('/fleet', [FleetController::class, 'store'])->name('fleet.store');
        Route::get('/fleet/{fleet}/edit', [FleetController::class, 'edit'])->name('fleet.edit')->whereNumber('fleet');
        Route::put('/fleet/{fleet}', [FleetController::class, 'update'])->name('fleet.update')->whereNumber('fleet');
        Route::patch('/fleet/{fleet}/toggle-status', [FleetController::class, 'toggleStatus'])->name('fleet.toggleStatus')->whereNumber('fleet');
    });

    /* ---------- Inventory ---------- */
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::post('/inventory/adjustments', [InventoryController::class, 'adjust'])
            ->name('inventory.adjust')
            ->middleware('permission:inventory.manage');
    });

    /* ---------- Deliveries ---------- */
    Route::middleware('permission:delivery.view')->group(function () {
        Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show')->whereNumber('delivery');
    });
    Route::middleware('permission:delivery.manage')->group(function () {
        Route::get('/deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
        Route::post('/deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
        Route::post('/deliveries/{delivery}/complete', [DeliveryController::class, 'complete'])->name('deliveries.complete');
        Route::post('/deliveries/{delivery}/cancel', [DeliveryController::class, 'cancel'])->name('deliveries.cancel');
    });

    /* ---------- Shifts ---------- */
    Route::middleware('permission:shift.view')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/create', [ShiftController::class, 'open'])->name('shifts.open');
    });
    Route::middleware('permission:shift.manage')->group(function () {
        Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
        Route::post('/shifts/{shift}/cancel', [ShiftController::class, 'cancel'])->name('shifts.cancel');
    });

    /* ---------- Reconciliations ---------- */
    Route::middleware('permission:reconciliation.view')->get('/reconciliations', [ReconciliationController::class, 'index'])->name('reconciliations.index');
    Route::middleware('permission:reconciliation.manage')->post('/reconciliations/run', [ReconciliationController::class, 'run'])->name('reconciliations.run');

    /* ---------- Expenses ---------- */
    Route::middleware('permission:expense.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });
    Route::middleware('permission:expense.manage')->group(function () {
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    });
    Route::middleware('permission:expense.approve')->post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');

    /* ---------- Suppliers ---------- */
    Route::middleware('permission:supplier.view')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    });
    Route::middleware('permission:supplier.manage')->group(function () {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit')->whereNumber('supplier');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->whereNumber('supplier');
        Route::patch('/suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggleStatus')->whereNumber('supplier');
    });

    /* ---------- Users & Roles ---------- */
    Route::middleware('permission:user.manage')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->whereNumber('user');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->whereNumber('user');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus')->whereNumber('user');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->whereNumber('user');
    });
    Route::middleware('permission:user.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show')->whereNumber('user');
    });

    /* ---------- Alerts & Notifications ---------- */
    Route::middleware('permission:alert.view')->group(function () {
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    /* ---------- Audit ---------- */
    Route::middleware('permission:audit.view')->get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    /* ---------- Settings ---------- */
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    /* ---------- Integrations ---------- */
    Route::middleware('permission:integration.manage')->group(function () {
        Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::post('/integrations/{integrationTransaction}/retry', [IntegrationController::class, 'retry'])->name('integrations.retry');
    });

    /* ---------- Reports ---------- */
    Route::middleware('permission:report.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');
    });
});