<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitializePaymentDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::whereNull('next_payment_due')->update([
            'payment_status' => true,
            'last_payment_date' => Carbon::now(),
            'next_payment_due' => Carbon::now()->addMonth(),
            'monthly_fee' => 50.00
        ]);
    }
}
