<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CheckpointController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class installPolicy extends Command
{
   private $test;
   /**
   * The name and signature of the console command.
   *
   * @var string
   */
   protected $signature = 'checkpoint:installPolicies';

   /**
   * The console command description.
   *
   * @var string
   */
   protected $description = 'Command to execute install policy in checkpoint';

   /**
   * Create a new command instance.
   *
   * @return void
   */
   public function __construct()
   {
      parent::__construct();
      $this->test = Session::get('time_execution');
      //$test = Session::get('time_execution');
   }

   /**
   * Execute the console command.
   *
   * @return mixed
   */
   public function handle(CheckpointController $checkpoint)
   {
      $checkpoint->installPolicy();
   }
}
