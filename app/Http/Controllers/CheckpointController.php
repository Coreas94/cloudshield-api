<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\Log;

use App\Http\Control;

class CheckpointController extends Controller
{
    public function test(Request $req){

        /*Control::ssh(['172.6.3.*', ['112','113']])->addObject("controlExample")->eSSH(function($resp, $cmd){
            Log::info("COMMAND: {$cmd}, RESPONSE: {$resp}");
        });*/
        /*return Control::curl("172.16.3.114")
          ->config(array(
              "user" => "test",
              "password" => "test",
              "continue-last-session" => true
          ))
          ->eCurl();*/
        //return 'ok';
    }
}
