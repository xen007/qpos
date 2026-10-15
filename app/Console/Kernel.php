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
        $schedule->command('backups:run --if-due --enqueue')->everyFiveMinutes()->timezone('Africa/Douala')->withoutOverlapping(30);
        $schedule->command('sales:expire-pending')->everyMinute()->withoutOverlapping(10);
        $schedule->command('reports:daily')->everyMinute()->timezone('Africa/Douala')->withoutOverlapping(10);
        $schedule->command('reports:deliver --limit=10')->everyMinute()->withoutOverlapping(10);
        $schedule->command('qpos:purchase-suggestions-daily --limit=5')->everyMinute()->timezone('Africa/Douala')->withoutOverlapping(10);
        $schedule->command('qpos:purchase-suggestions-weekly --limit=5')->everyMinute()->timezone('Africa/Douala')->withoutOverlapping(10);
        $schedule->command('qpos:purchase-alerts-critical --limit=200')->everyMinute()->timezone('Africa/Douala')->withoutOverlapping(10);
        $schedule->command('notifications:pump --limit=500')->everyMinute()->timezone('Africa/Douala')->withoutOverlapping(10);
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
