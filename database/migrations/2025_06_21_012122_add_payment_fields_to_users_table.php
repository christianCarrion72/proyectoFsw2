<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('payment_status')->default(true); // true = pagado, false = bloqueado
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_due')->nullable();
            $table->decimal('monthly_fee', 8, 2)->default(50.00); // Tarifa mensual
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'last_payment_date', 'next_payment_due', 'monthly_fee']);
        });
    }
};