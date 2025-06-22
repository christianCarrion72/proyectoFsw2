<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckPaymentStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = null;
        
        // Verificar múltiples guards
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $user = $admin->persona->user ?? null;
        } elseif (Auth::guard('guardia')->check()) {
            $guardia = Auth::guard('guardia')->user();
            $user = $guardia->persona->user ?? null;
        } elseif (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        }
        
        if ($user && !$user->hasValidPayment()) {
            return redirect()->route('payment.blocked')
                ->with('error', 'Tu suscripción ha vencido. Debes realizar el pago para continuar.');
        }

        return $next($request);
    }
}
