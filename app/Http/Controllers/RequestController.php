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
use App\FwCompanyServer;
use App\FwObject;
use App\FwServer;
use App\AddressObject;
use App\FwSectionAccess;
use App\FwAccessRule;
use App\CheckPointRulesObjects;
use App\ServicesCheckpoint;
use App\Http\Controllers\EmailController;
use App\HistoricalData;
use App\RequestIp;
use App\Jobs\senderEmailIp;
use Mail;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;

class RequestController extends Controller{
   private $output = "";
   private $verification;
   private $verif_obj;
   private $prueba = [];

   public function __construct(){
      $evaluate = "";
  	}

   public function saveRequestIp(Request $request){

      $user = JWTAuth::toUser($request['token']);
      Log::info($user);

      $company_id = $user['company_id'];
      $request_user_id = $user['id'];
      $object_id = $request['object_id'];
      $type_request = $request['type_request'];
      $user_name = $user['name'].' '.$user['lastname'];
      $array_ip = [];

      $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
      $company_data2 = json_decode(json_encode($company_data), true);

      $name_company = $company_data2[0]['name'];

      foreach($request['ips'] as $value){
			$ip_initial = $value['ip_initial'];
			$ip_last = $value['ip_last'];

         array_push($array_ip, $ip_initial.'-'.$ip_last);

         $request = new RequestIp;
         $request->ip_initial = $ip_initial;
         $request->ip_last = $ip_last;
         $request->object_id = $object_id;
         $request->company_id = $company_id;
         $request->type_request = $type_request;
         $request->request_user_id = $request_user_id;
         $request->status = 0;
         $request->save();
      }

      if($request->id){
         $title = 'CloudShield - Alert Request';
         $data = "Se informa que se recibió una solicitud del usuario: ".$user_name." perteneciente a la empresa: ".$name_company;
         $data2 = "Para agregar las siguientes IPs: ".implode(", ", $array_ip);

         Mail::send('email.alert_request', ['title' => $title, 'data' => $data, "data2" => $data2], function ($message){
            $message->subject('CloudShield - Alarma de solicitud');
            $message->from('alerts@red4g.net', 'CloudShield');
            $message->to('jcoreas@red4g.net');
         });

         return response()->json([
 				'success' => [
 					'message' => 'Request Success',
 					'status_code' => 200
 				]
 			]);
      }else{
         return response()->json([
 				'error' => [
 					'message' => 'Request Error',
 					'status_code' => 20
 				]
 			]);
      }
   }

   public function getAllRequest(Request $request){

      $user = JWTAuth::toUser($request['token']);
 		$company_id = $user['company_id'];
 		$role_user = $user->roles->first()->name;

      if($role_user == "superadmin"){
         $request = RequestIp::join('fw_companies', 'request_ips.company_id', '=', 'fw_companies.id')
            ->join('fw_objects', 'request_ips.object_id', '=', 'fw_objects.id')
            ->join('users', 'request_ips.request_user_id', '=', 'users.id')
            ->where('request_ips.status', '=', 0)
            ->select('request_ips.*', 'fw_objects.name AS object_name', 'fw_companies.name AS company', 'users.name', 'users.lastname')
            ->get();
      }else{
         $request = RequestIp::join('fw_companies', 'request_ips.company_id', '=', 'fw_companies.id')
            ->join('fw_objects', 'request_ips.object_id', '=', 'fw_objects.id')
            ->join('users', 'request_ips.request_user_id', '=', 'users.id')
            ->where('request_ips.status', '=', 0)
            ->where('request_ips.company_id', '=', $company_id)
            ->select('request_ips.*', 'fw_objects.name AS object_name', 'fw_companies.name AS company', 'users.name', 'users.lastname')
            ->get();
      }

      $list_obj = [];
 		$name2 = [];
 		foreach ($request as  $value) {
         if (strpos($value['object_name'], 'IP-ADDRESS') !== false ) {
            $name = explode('-', $value['object_name']);
            $complement_name = $name[2].' '.$name[3];

            $value['short_name'] = 'MY CLOUDSHIELD '.$complement_name;
            array_push($list_obj, $value);
         }
 		}

 		$new_obj = json_decode(json_encode($list_obj), true);
      $count_request = count($request);

      if($count_request > 0){

         $list_request = json_decode(json_encode($new_obj), true);

   		return response()->json([
   			'data' => $list_request
   		]);
      }else{
         return response()->json([
   			'data' => "No data"
   		]);
      }
   }

   public function acceptRequest(Request $request){

   }

   public function declineRequest(Request $request){

   }

   public function countRequest(Request $request){

      $user = JWTAuth::toUser($request['token']);
      $company_id = $user['company_id'];
 		$role_user = $user->roles->first()->name;

      if($role_user == "superadmin"){
         $count = RequestIp::where('request_ips.status', '=', 0)->count();
      }else{
         $count = RequestIp::where('request_ips.status', '=', 0)->where('request_ips.company_id', '=', $company_id)->count();
      }

      return response()->json([
         'count' => $count
      ]);

   }


}
