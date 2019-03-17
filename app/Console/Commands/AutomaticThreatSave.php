<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FortisiemController;
use App\Http\Controllers\AutomaticData;
use Illuminate\Support\Facades\Log;

class AutomaticThreatSave extends Command
{
   /**
   * The name and signature of the console command.
   *
   * @var string
   */
   protected $signature = 'fortisiem:automaticThreatSave';

   /**
   * The console command description.
   *
   * @var string
   */
   protected $description = 'Command description';

   /**
   * Create a new command instance.
   *
   * @return void
   */
   public function __construct()
   {
     parent::__construct();
   }

   /**
   * Execute the console command.
   *
   * @return mixed
   */
   public function handle(AutomaticData $automatic)
   {
      Log::info("Llega a threat automatic");
      $automatic->getData();
   }
}
