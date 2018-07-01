<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\Log;

use App\Http\Control;

class CheckpointController extends Controller
{
    public function test(Request $req){

        /*Control::ssh(['172.6.3.*', ['112','113']])->addObject()->eSSH(function($resp, $cmd){

        });*/
    }
}
