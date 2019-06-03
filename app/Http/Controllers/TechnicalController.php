<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use phpseclib\Net\SFTP;
use App\Jobs\senderEmailIp;
use App\Jobs\BackgroundTask;
use Mail;
use File;
use Artisan;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;

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

      $emailCtrl = new EmailController;

      $company_id = $request['company_id'];//compañía que se está modificando
      $comment = $request['comment'];
      $user_id = $request['user_id'];

      $update_company = Company::where('id', $company_id)
         ->update(['config' => 1]);

      $log_config = DB::table('config_company_logs')->insert(
         ['company_id' => $company_id, 'user_id' => $user_id, "comment" => $comment, 'created_at' => \Carbon\Carbon::now(), 'updated_at' =>\Carbon\Carbon::now()]
      );

      $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
      $company_data2 = json_decode(json_encode($company_data), true);

      $name_company = $company_data2[0]['name'];

      $data_email = array("name_company" => $name_company, 'type_ssh' => 'confirm_enable_company');

      $emailCtrl->sendEmailSSHObj($data_email);

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
         $array = [];

         $config = json_decode(json_encode($config), true);

         foreach($config as $value){
            Log::info($value);
            $value['id'] = $value['id'];
   			$value['username'] = $value['username'];
   			$value['password'] = Crypt::decrypt($value['password']);

   			array_push($array, $value);
         }

         return response()->json([
            'success' => [
               'message' => $array,
               'status_code' => 200
            ]
         ]);
      }
   }

}
