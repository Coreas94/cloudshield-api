<?php

namespace App\Jobs;

use App\User;
use App\Jobs\Job;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class senderEmailIp extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $title;
    protected $data;
    protected $user;

    public function __construct($title, $data)
    {
        $this->title = $title;
        $this->data = $data;
        //$this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('LLEGA AL JOB');

        $title2 = $this->title;
        $data2 = $this->data;
        //$user2 = $this->user;


        \Mail::send('email.send', ['title' => $title2, 'data' => $data2], function ($message){
            
            $message->subject('[servidores #11403] CONTROL4 - Bitácora de registro de IPs - White List');
            $message->from('servers-comment@request.red4g.net', 'servers-comment');
            $message->to('jcoreas@red4g.net');
        });
    }
}
