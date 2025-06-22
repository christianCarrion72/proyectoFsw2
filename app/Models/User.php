<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'payment_status',
        'last_payment_date',
        'next_payment_due',
        'monthly_fee',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'payment_status' => 'boolean',
        'last_payment_date' => 'date',
        'next_payment_due' => 'date',
        'monthly_fee' => 'decimal:2',
    ];

    /**
     * Verificar si el usuario tiene el pago al día
     */
    public function hasValidPayment(): bool
    {
        return $this->payment_status && 
               ($this->next_payment_due === null || $this->next_payment_due->isFuture());
    }

    /**
     * Marcar como bloqueado por falta de pago
     */
    public function blockForNonPayment(): void
    {
        $this->update([
            'payment_status' => false,
            'next_payment_due' => Carbon::now()->addMonth()
        ]);
    }

    /**
     * Procesar pago y desbloquear
     */
    public function processPayment(): void
    {
        $this->update([
            'payment_status' => true,
            'last_payment_date' => Carbon::now(),
            'next_payment_due' => Carbon::now()->addMonth()
        ]);
    }

    /**
     * Relación con Persona
     */
    public function persona()
    {
        return $this->hasOne(Persona::class);
    }

    /**
     * Relación con Suscripciones
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Obtener la suscripción activa
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
                   ->where('status', 'completed')
                   ->where('subscription_end_date', '>=', Carbon::now())
                   ->latest('subscription_end_date')
                   ->first();
    }

    /**
     * Verificar si tiene suscripción activa
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription() !== null;
    }
}
