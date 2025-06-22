<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BlockUsersForNonPayment extends Command
{
    protected $signature = 'users:block-non-payment';
    protected $description = 'Bloquear usuarios que no han pagado al final del mes';

    public function handle()
    {
        $today = Carbon::now();
        
        // Buscar usuarios cuya fecha de vencimiento ya pasó
        $usersToBlock = User::where('payment_status', true)
            ->where('next_payment_due', '<=', $today)
            ->get();

        $blockedCount = 0;
        
        foreach ($usersToBlock as $user) {
            $user->blockForNonPayment();
            $blockedCount++;
            $this->info("Usuario bloqueado: {$user->email}");
        }

        $this->info("Total de usuarios bloqueados: {$blockedCount}");
        
        return Command::SUCCESS;
    }
}
