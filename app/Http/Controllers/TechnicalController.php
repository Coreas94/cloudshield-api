<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use phpseclib\Net\SFTP;
use App\Jobs\senderEmailIp;
use App\Jobs\BackgroundTask;
use Mail;
use File;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Artisan;

use JWTAuth;
use App\Company;
use App\FwCompanyServer;
use App\FwObject;
use App\FwServer;
use App\AddressObject;
use App\FwSectionAccess;
use App\FwAccessRule;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;
use Carbon\Carbon;

class TechnicalController extends Controller{

   public function getNewCompanies(Request $request){

      $data = Company::where('fw_companies.config', 0)
         ->get();

      if(count($data) == 0){
         return response()->json([
   			'success' => [
   				'data' => [],
   				'status_code' => 200
   			]
   	 	]);
      }else{
         return response()->json([
   			'success' => [
   				'data' => $data,
   				'status_code' => 200
   			]
   	 	]);
      }
   }

   public function editConfigCompany(Request $request){

      $company_id = $request['company_id'];
      $comment = $request['comment'];
      $user_id = $request['user_id'];

      $update_company = Company::where('id', $company_id)
         ->update(['config' => 1]);

      $log_config = DB::table('config_company_logs')->insert(
         ['company_id' => $company_id, 'user_id' => $user_id, "commemt" => $comment, 'created_at' => \Carbon\Carbon::now(), 'updated_at' =>\Carbon\Carbon::now()]
      );

      if($update_company){
         return response()->json([
            'success' => [
               'data' => "Configuración actualizada",
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => 'Company not edited',
               'status_code' => 20
            ]
         ]);
      }
   }

   public function validateConfigData(Request $request){
      Log::info($request);
      //die();
      $credentials = $request->only('email', 'password');

      $token = NULL;
      if (! $token = auth()->attempt($credentials)) {
         return response()->json([
            'error' => [
               'message' => 'No tienes permiso',
               'status_code' => 20
            ]
         ]);
      }else{
         $config = DB::table('config_company')->where('company_id', $request['company_id'])->get();

         return response()->json([
            'success' => [
               'message' => $config,
               'status_code' => 200
            ]
         ]);

      }

   }

}
