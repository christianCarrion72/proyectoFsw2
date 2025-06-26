<?php

use App\Http\Controllers\Admin\GuardiaManagementController;
use App\Http\Controllers\Web\GuardiaControllerWeb;
use App\Http\Controllers\Web\AdministradorControllerWeb;
use App\Http\Controllers\Web\EventoControllerWeb;
use App\Http\Controllers\Web\EvidenciaControllerWeb;
use App\Http\Controllers\Web\InformeControllerWeb;
use App\Http\Controllers\Web\PageControllerWeb;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BullyingDataController;
use Illuminate\Support\Facades\Route;


Route::get('/', [PageControllerWeb::class, 'welcome'])->name('welcome');

Route::prefix('admin')->group(function () {
    /* Login routes */
    Route::middleware(['guest:admin', 'guest:guardia'])->group(function () {
        Route::get('/login', [AdministradorControllerWeb::class, 'loginView'])->name('admin.login.view');
        Route::post('/login', [AdministradorControllerWeb::class, 'login'])->name('admin.login');
    });

    /* Admin protected routes */
    Route::middleware(['auth:admin', 'check.payment'])->group(function () {
        Route::get('/dashboard', [AdministradorControllerWeb::class, 'dashboardView'])->name('admin.dashboard');
        Route::post('/logout',[AdministradorControllerWeb::class,'logout'])->name('admin.logout');
        Route::get('/informes', [InformeControllerWeb::class, 'indexAdmin'])->name('admin.informes.index');
        
        // Rutas para gestión de guardias
        Route::resource('guardias', GuardiaManagementController::class, [
            'as' => 'admin'
        ]);
    });
});

Route::prefix('guardia')->group(function () {
    /* Login routes */
    Route::middleware(['guest:admin', 'guest:guardia'])->group(function () {
        Route::get('/login', [GuardiaControllerWeb::class, 'loginView'])->name('guardia.login.view');
        Route::post('/login', [GuardiaControllerWeb::class, 'login'])->name('guardia.login');
    });

    /* Guardia protected routes with payment check */
    Route::middleware(['auth:guardia', 'check.payment'])->group(function () {
        Route::get('/dashboard', [GuardiaControllerWeb::class, 'dashboardView'])->name('guardia.dashboard');
        Route::post('/logout',[GuardiaControllerWeb::class,'logout'])->name('guardia.logout');
        Route::get('/informes', [InformeControllerWeb::class, 'indexGuardia'])->name('guardia.informes.index');
        Route::get('/eventos', [EventoControllerWeb::class, 'indexGuardia'])->name('guardia.eventos.index');
        Route::get('/crear_informe/{evento}', [InformeControllerWeb::class, 'createView'])->name('guardia.informe.crear');
        Route::post('/crear_informe/{evento}', [InformeControllerWeb::class, 'store'])->name('guardia.informe.store');
        Route::get('/evidencias/{evento}', [EvidenciaControllerWeb::class, 'indexGuardia'])->name('guardia.evidencia.index');
    });
});

// Rutas de pago - SIN middleware auth para la vista blocked
Route::get('/payment/blocked', [PaymentController::class, 'blocked'])->name('payment.blocked');

// Rutas de procesamiento de pago - CON middleware para múltiples guards
Route::post('/payment/process', [PaymentController::class, 'processPayment'])
    ->middleware(['auth:admin,guardia,web'])
    ->name('payment.process');

// Nuevas rutas para los diferentes métodos de pago
Route::post('/payment/process/card', [PaymentController::class, 'processCardPayment'])
    ->middleware(['auth:admin,guardia,web'])
    ->name('payment.process.card');

Route::post('/payment/process/paypal', [PaymentController::class, 'processPayPalPayment'])
    ->middleware(['auth:admin,guardia,web'])
    ->name('payment.process.paypal');

Route::post('/payment/process/crypto', [PaymentController::class, 'processCryptoPayment'])
    ->middleware(['auth:admin,guardia,web'])
    ->name('payment.process.crypto');

// Rutas específicas para logout desde payment blocked
Route::post('/payment/admin-logout', function() {
    auth()->guard('admin')->logout();
    return redirect()->route('welcome');
})->name('payment.admin.logout');

Route::post('/payment/guardia-logout', function() {
    auth()->guard('guardia')->logout();
    return redirect()->route('welcome');
})->name('payment.guardia.logout');

// Rutas para PayPal
Route::post('/payment/paypal/success', [PaymentController::class, 'paypalSuccessJS'])->name('paypal.success.js');
Route::get('/payment/paypal/success', [PaymentController::class, 'paypalSuccess'])->name('paypal.success');
Route::get('/payment/paypal/cancel', [PaymentController::class, 'paypalCancel'])->name('paypal.cancel');

// Rutas para suscripciones
Route::middleware('auth:admin,guardia')->group(function () {
    Route::get('/subscriptions', [PaymentController::class, 'showSubscriptions'])->name('subscriptions.index');
    // Rutas de exportación del índice (deben ir antes de las rutas con parámetros)
    Route::get('/subscriptions/export/pdf', [PaymentController::class, 'exportSubscriptionsIndexPdf'])->name('subscriptions.index.export.pdf');
    Route::get('/subscriptions/export/excel', [PaymentController::class, 'exportSubscriptionsIndexExcel'])->name('subscriptions.index.export.excel');
    // Rutas con parámetros (deben ir después de las rutas específicas)
    Route::get('/subscriptions/{subscription}', [PaymentController::class, 'showSubscription'])->name('subscriptions.show');
    Route::get('/subscriptions/{subscription}/pdf', [PaymentController::class, 'exportSubscriptionPdf'])->name('subscriptions.export.pdf');
    Route::get('/subscriptions/{subscription}/excel', [PaymentController::class, 'exportSubscriptionExcel'])->name('subscriptions.export.excel');
});

// Ruta pública para demostración del dashboard de bullying
Route::get('/dashboard-demo', function () {
    return view('guardia.dashboard');
})->name('dashboard.demo');

// Rutas API para datos de bullying (con autenticación)
Route::middleware(['auth:admin,guardia,web'])->prefix('api/bullying')->group(function () {
    Route::get('/kpis', [BullyingDataController::class, 'getKPIs'])->name('api.bullying.kpis');
    Route::get('/incidents', [BullyingDataController::class, 'getIncidents'])->name('api.bullying.incidents');
    Route::get('/chart/{type}', [BullyingDataController::class, 'getChartData'])->name('api.bullying.chart');
    Route::post('/regenerate', [BullyingDataController::class, 'regenerateData'])->name('api.bullying.regenerate');
});

// Rutas API públicas para demostración del dashboard
Route::prefix('api/bullying')->group(function () {
    Route::get('/kpis', [BullyingDataController::class, 'getKPIs'])->name('api.bullying.kpis.public');
    Route::get('/incidents', [BullyingDataController::class, 'getIncidents'])->name('api.bullying.incidents.public');
    Route::get('/chart/{type}', [BullyingDataController::class, 'getChartData'])->name('api.bullying.chart.public');
    Route::post('/regenerate', [BullyingDataController::class, 'regenerateData'])->name('api.bullying.regenerate.public');
});