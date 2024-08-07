<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FortisiemController;
use Illuminate\Support\Facades\Log;

class dataFortisiem extends Command
{
   /**
   * The name and signature of the console command.
   *
   * @var string
   */
   protected $signature = 'fortisiem:getLogsFortisiem';

   /**
   * The console command description.
   *
   * @var string
   */
   protected $description = 'Command get logs from fortisiem';

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
   public function handle(FortisiemController $fortisiem)
   {
      Log::info("llega a ejecutar");
      $fortisiem->runScriptLogs();

   }
}
