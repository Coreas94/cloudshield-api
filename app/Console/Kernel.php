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
     Commands\resendDataCheckpoint::class
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


   }
}
