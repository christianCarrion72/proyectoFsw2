<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function blocked()
    {
        return view('payment.blocked');
    }

    public function processPayment(Request $request)
    {
        $user = null;
        $redirectRoute = 'welcome';
        
        // Determinar qué guard está autenticado
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $user = $admin->persona->user ?? null;
            $redirectRoute = 'admin.dashboard';
        } elseif (Auth::guard('guardia')->check()) {
            $guardia = Auth::guard('guardia')->user();
            $user = $guardia->persona->user ?? null;
            $redirectRoute = 'guardia.dashboard';
        } elseif (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $redirectRoute = 'dashboard'; // o la ruta que uses para usuarios web
        }
        
        if (!$user) {
            return redirect()->route('payment.blocked')->with('error', 'No se pudo encontrar el usuario asociado');
        }

        // Simular procesamiento de pago
        $user->update([
            'payment_status' => true,
            'last_payment_date' => now(),
            'next_payment_due' => now()->addMonth(),
        ]);

        return redirect()->route($redirectRoute)->with('success', 'Pago procesado exitosamente');
    }
}
