<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
   /**
   * The Artisan commands provided by your application.
   *
   * @var array
   */
   protected $commands = [
     // Commands\Inspire::class,
     Commands\dataFortisiem::class,
     //Commands\installPolicy::class,
     Commands\resendDataCheckpoint::class,
     Commands\AutomaticThreat::class,
     Commands\AutomaticThreatSave::class,
   ];

   /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
   */
   protected function schedule(Schedule $schedule)
   {
      //$schedule->command('inspire')
      //->hourly();

      $schedule->command('fortisiem:getLogsFortisiem')->cron('*/5 * * * *');

      $schedule->command('checkpoint:resendData')->everyFiveMinutes();

      $schedule->command('fortisiem:automaticThreat')->cron('*/10 * * * *');

      $schedule->command('fortisiem:automaticThreatSave')->cron('*/12 * * * *');

      //$schedule->command('fortisiem:automaticPALogs')->cron('*/14 * * * *');

   }
}
