<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar el comando el último día de cada mes a las 23:59
        $schedule->command('users:block-non-payment')
                 ->monthlyOn(31, '23:59');
                
        // También ejecutar el primer día del mes por si el mes no tiene 31 días
        $schedule->command('users:block-non-payment')
                 ->monthlyOn(1, '00:01');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
