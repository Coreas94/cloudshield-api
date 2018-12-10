<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\ValidateCommandController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class resendDataCheckpoint extends Command
{
   /**
   * The name and signature of the console command.
   *
   * @var string
   */
   protected $signature = 'checkpoint:resendData';
   // protected $signature = 'checkpoint:resendData {token}';

   /**
   * The console command description.
   *
   * @var string
   */
   protected $description = 'Command for resend data temp to checkpoint';

   /**
   * Create a new command instance.
   *
   * @return void
   */
   public function __construct()
   {
     parent::__construct();
      #$this->token = $token;
   }

   /**
   * Execute the console command.
   *
   * @return mixed
   */
   public function handle(ValidateCommandController $validate){
      Log::info("LLega al resend");
      //$token = $this->argument('token');
      //$validate->resendDataTemp($token);

      //$validate->getErrorData();
   }
}
