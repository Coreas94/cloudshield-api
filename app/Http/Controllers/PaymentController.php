<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection; // to generate collections.
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Plans;
use App\CompanyPlan;

class PaymentController extends Controller{

   function func_x1($n){
      return sqrt(($n - 4) / 4);
   }

   function func_x2($n){
      return sqrt($n - 10);
   }

   function func_x3($n){
      return 2 - $n;
   }

   public function decoder(Request $request){

      $DATA = $request['data'];

      $content = base64_decode(explode('@', str_replace('#', '', $DATA))[1]);
      $normalized = [[1,3,5,7],[9,11,13,15],[2,4,6,8],[10,12,14,16]];
      $blob = json_decode($content);

      $nOrder = [];
      $toDivide = [];
      $order = [];
      $decode = [];
      $code = $blob->crd;
      $sc = $blob->cv;
      $ed = $blob->ed;
      $tc = $blob->tc;
      $blobCode = json_decode(base64_decode($code));

      foreach ($blobCode as $key_0 => $value_0) {
        $toDivide = $normalized[$key_0];
        foreach ($value_0 as $key_1 => $value_1) {
            $num = $value_1 / $toDivide[$key_1];
            array_push($order, array(
                'n' => $num,
                'i' => $toDivide[$key_1]
            ));
         }
      }

      for($i = 0; $i < 16; $i++){
        foreach ($order as $key => $value) {
            $index = $i + 1;
            if($value['i'] == $index){
                if($value['i'] % 2 != 0) array_push($nOrder, $this->func_x1($value['n']));
                else array_push($nOrder, $this->func_x2($value['n']));
            }
         }
      }

      $val_0 = join(array_slice($nOrder, 0, 4));
      $val_1 = join(array_slice($nOrder, 4, 4));
      $val_2 = join(array_slice($nOrder, 8, 4));
      $val_3 = join(array_slice($nOrder, 12, 4));

      $response = $val_0.$val_1.$val_2.$val_3;
      $ccv = $this->func_x3($sc);
      $edate = base64_decode($ed);
      $tcard = base64_decode($tc);

      print "${response} <br>";   // ORIGINAL CREDIT CARD
      print "${ccv} <br>";        // CCV
      print "${edate} <br>";      // EXPIRATION DATE
      print "${tcard} <br>";      // TYPE CARD
   }




}
