<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Artisan;

use JWTAuth;
use App\Company;
use App\HistoricalData;

class HistoryController extends Controller{


   public function getHistoricalData(Request $request){

      // $company_id = 1;
      $company_id = $request['company_id'];

      if($company_id = 1){
         $success = HistoricalData::where('status', '=', 1)->orderBy('created_at', 'desc')->take(15)->get();
         $fail = HistoricalData::where('status', '=', 0)->orderBy('created_at', 'desc')->take(15)->get();
      }else{
         $success = HistoricalData::where('status', '=', 1)->where('company_id', '=', $company_id)->orderBy('created_at', 'desc')->take(5)->get();
         $fail = HistoricalData::where('status', '=', 0)->where('company_id', '=', $company_id)->orderBy('created_at', 'desc')->take(5)->get();
      }

      return response()->json([
         'success' => $success,
			'error' => $fail
		]);

   }

}
