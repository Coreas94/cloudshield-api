<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Log;

class AutomaticPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:automaticPayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for payment automatic';

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
    public function handle(PaymentController $payment)
    {
        $payment->getAutomaticPayment();
    }
}
