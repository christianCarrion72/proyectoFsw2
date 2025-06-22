<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Información del pago
            $table->enum('payment_method', ['stripe', 'paypal', 'crypto']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            
            // Datos específicos por método de pago
            // Para Stripe
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            
            // Para PayPal
            $table->string('paypal_order_id')->nullable();
            $table->string('paypal_payer_id')->nullable();
            $table->json('paypal_transaction_details')->nullable();
            
            // Para Crypto
            $table->string('crypto_transaction_hash')->nullable();
            $table->string('crypto_token_type')->nullable(); // ETH, USDT, BNB
            $table->string('crypto_wallet_address')->nullable();
            $table->json('crypto_transaction_details')->nullable();
            
            // Fechas de suscripción
            $table->date('subscription_start_date');
            $table->date('subscription_end_date');
            $table->date('payment_date');
            
            // Información adicional
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Para datos adicionales
            
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['payment_method', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};