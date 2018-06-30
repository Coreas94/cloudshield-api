<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use JWTAuth as JwT;

class CheckpointController extends Controller
{
    public function test(Request $req){
        return $req['token'];
    }
}
