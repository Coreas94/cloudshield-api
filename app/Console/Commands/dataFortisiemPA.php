<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FortisiemController;
use Illuminate\Support\Facades\Log;

class dataFortisiemPA extends Command
{
   /**
   * The name and signature of the console command.
   *
   * @var string
   */
   protected $signature = 'fortisiem:getLogsFortisiemPA';

   /**
   * The console command description.
   *
   * @var string
   */
   protected $description = 'Get logs Palo Alto';

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
   public function handle(FortisiemController $fortisiem){
      Log::info("llega a ejecutar");
      $fortisiem->runScriptPA();
   }
}
