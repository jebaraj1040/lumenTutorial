<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\UpsertMasterDataCommand::class,
        Commands\CCPushCommand::class,
        Commands\ApplicationActivityStatus::class
        // TODO need to enable once stage sms works
        // Commands\DropoffSms::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Daily update schedules.
        $schedule->command('UpsertMasterData:Records')
            ->dailyAt(12.05);
        $schedule->command('CCPush:Request')
            ->everyFiveMinutes();
        $schedule->command('ApplicationActivity:Records')
            ->dailyAt(12.10);
        // TODO need to enable once stage sms works
        // $schedule->command('Dropoff:Sms')
        //  ->everyFiveMinutes();
    }
}
