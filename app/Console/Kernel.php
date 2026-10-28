<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Modules\Accounts\Jobs\AcRecurringActionJob;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\MainMigrateSeed::class,
        \App\Console\Commands\ModuleInstall::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('attendance:cron')
            ->everyMinute();
        

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
