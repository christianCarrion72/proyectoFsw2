<?php

use App\Http\Controllers\Admin\GuardiaManagementController;
use App\Http\Controllers\Web\GuardiaControllerWeb;
use App\Http\Controllers\Web\AdministradorControllerWeb;
use App\Http\Controllers\Web\EventoControllerWeb;
use App\Http\Controllers\Web\EvidenciaControllerWeb;
use App\Http\Controllers\Web\InformeControllerWeb;
use App\Http\Controllers\Web\PageControllerWeb;
use App\Http\Controllers\PaymentController;
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
    Route::get('/subscriptions/{subscription}', [PaymentController::class, 'showSubscription'])->name('subscriptions.show');
});