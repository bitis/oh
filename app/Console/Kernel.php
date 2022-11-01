<?php

namespace App\Console;

use App\Console\Commands\Xhs;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $accounts = config('watch.XiaomiSportsAccounts');

            foreach (explode(';', $accounts) as $account) {
                $this->call("XiaomiSports $account");
            }
        })->dailyAt('8:01');

        $schedule->call('t66y')->everyFiveMinutes();
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
