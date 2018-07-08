<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\Log;

use App\Http\Control;

class CheckpointController extends Controller
{
    public function test(Request $req){

        /*return Control::ssh(['172.16.3.*', ['112', '113']])->raw("-a display")->eSSH(function($response){
            Log::info($response);
        }, false);*/
        return Control::curl("172.16.3.114")
          ->config(array(
              "user" => "test",
              "password" => "test",
              "continue-last-session" => true
          ))->eCurl();
        //return 'ok';
    }
}
