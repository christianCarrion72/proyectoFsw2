<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_payment_method_id',
        'paypal_order_id',
        'paypal_payer_id',
        'paypal_transaction_details',
        'crypto_transaction_hash',
        'crypto_token_type',
        'crypto_wallet_address',
        'crypto_transaction_details',
        'subscription_start_date',
        'subscription_end_date',
        'payment_date',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paypal_transaction_details' => 'array',
        'crypto_transaction_details' => 'array',
        'metadata' => 'array',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'payment_date' => 'date',
    ];

    // Relación con User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'completed')
                    ->where('subscription_end_date', '>=', Carbon::now());
    }

    // Métodos auxiliares
    public function isActive()
    {
        return $this->status === 'completed' && 
               $this->subscription_end_date >= Carbon::now();
    }

    public function isExpired()
    {
        return $this->subscription_end_date < Carbon::now();
    }

    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 2);
    }
}