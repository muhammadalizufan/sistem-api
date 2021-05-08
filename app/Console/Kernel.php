<?php

namespace App\Console;

use App\Models\SIAP\IncomingLetter;
use Carbon\Carbon;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            IncomingLetter::where('dateline', '<', Carbon::now())->where('status', 0)->update([
                'status' => 2,
            ]);
        })->everyMinute();
    }
}
