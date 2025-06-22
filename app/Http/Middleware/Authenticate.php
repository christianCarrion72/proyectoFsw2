<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Verificar si es una ruta de admin
        if ($request->is('admin/*')) {
            return route('admin.login.view');
        }
        
        // Verificar si es una ruta de guardia
        if ($request->is('guardia/*')) {
            return route('guardia.login.view');
        }
        
        // Por defecto, redirigir a welcome
        return $request->expectsJson() ? null : route('welcome');
    }
}
