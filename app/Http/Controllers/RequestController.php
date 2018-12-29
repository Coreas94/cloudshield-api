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
use App\Http\Controllers\CheckpointController;

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

         $request_all = RequestIp::join('fw_companies', 'request_ips.company_id', '=', 'fw_companies.id')
            ->join('fw_objects', 'request_ips.object_id', '=', 'fw_objects.id')
            ->join('users', 'request_ips.request_user_id', '=', 'users.id')
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

         $request_all = RequestIp::join('fw_companies', 'request_ips.company_id', '=', 'fw_companies.id')
            ->join('fw_objects', 'request_ips.object_id', '=', 'fw_objects.id')
            ->join('users', 'request_ips.request_user_id', '=', 'users.id')
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

      $list_obj2 = [];
 		$name3 = [];
 		foreach ($request_all as  $value) {
         if (strpos($value['object_name'], 'IP-ADDRESS') !== false ) {
            $name = explode('-', $value['object_name']);
            $complement_name = $name[2].' '.$name[3];

            $value['short_name'] = 'MY CLOUDSHIELD '.$complement_name;
            array_push($list_obj2, $value);
         }
 		}

      $new_obj = json_decode(json_encode($list_obj), true);
 		$new_obj_all = json_decode(json_encode($list_obj2), true);

      $count_request = count($request);

      if($count_request > 0){

         $list_request = json_decode(json_encode($new_obj), true);
         $list_request_all = json_decode(json_encode($new_obj_all), true);
         Log::info($list_request);
   		return response()->json([
   			'data' => $list_request,
            'data_all' => $list_request_all
   		]);
      }else{
         return response()->json([
   			'data' => "No data",
            'data_all' => $new_obj_all
   		]);
      }
   }

   public function acceptRequest(Request $request){
      $checkpoint = new CheckpointController;

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

      $request_id = $request['id_request'];
      $request_data = RequestIp::where('id', '=', $request_id)->get();
      $request_data = $request_data->toArray();

      foreach($request_data as $key => $row){
         array_push($array_ip, $row['ip_initial'].'-'.$row['ip_last']);
         $ips = array('ip_initial' => $row['ip_initial'], 'ip_last' => $row['ip_last']);
         $data_send = array('token' => $request['token'], 'object_id' => $row['object_id'], 'ips' => array($ips));
      }

      $data = new Request($data_send);
      $assign = $checkpoint->assignIpObject($data);

      $assign_response = json_decode($assign->content(), true);

      foreach ($assign_response as $key => $value) {
         if($key == "success"){

            $request_upd = RequestIp::find($request_id);
            $request_upd->status = 1;
            $request_upd->save();

            $title = 'CloudShield - Alert Request';
            $data = "Se informa que se aceptó la solicitud del usuario: ".$user_name." perteneciente a la empresa: ".$name_company;
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
    					'message' => 'Request error',
    					'status_code' => 20
    				]
    			]);
         }
      }
   }

   public function declineRequest(Request $request){

      $user = JWTAuth::toUser($request['token']);
      Log::info($user);

      $company_id = $user['company_id'];
      $request_user_id = $user['id'];
      $user_name = $user['name'].' '.$user['lastname'];
      $array_ip = [];

      $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
      $company_data2 = json_decode(json_encode($company_data), true);

      $name_company = $company_data2[0]['name'];

      $request_id = $request['id_request'];
      $request_data = RequestIp::where('id', '=', $request_id)->get();
      $request_data = $request_data->toArray();

      foreach($request_data as $key => $row){
         array_push($array_ip, $row['ip_initial'].'-'.$row['ip_last']);
      }

      $request_upd = RequestIp::find($request_id);
      $request_upd->status = 2;
      $request_upd->save();

      $title = 'CloudShield - Alert Request';
      $data = "Se informa que se rechazó la solicitud del usuario: ".$user_name." perteneciente a la empresa: ".$name_company;
      $data2 = "Para agregar las siguientes IPs: ".implode(", ", $array_ip);

      Mail::send('email.alert_request', ['title' => $title, 'data' => $data, "data2" => $data2], function ($message){
         $message->subject('CloudShield - Alarma de solicitud');
         $message->from('alerts@red4g.net', 'CloudShield');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json([
         'success' => [
            'message' => 'Solicitud rechazada',
            'status_code' => 200
         ]
      ]);
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
