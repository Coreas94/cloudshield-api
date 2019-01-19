<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\ObjectAddr;
use App\Policies;
use App\Company;
use App\PoliciesSource;
use App\PoliciesDestination;
use App\FwCompanyServer;
use App\FwObject;
use App\AddressObject;
use App\Http\Controllers\CheckPointFunctionController;

use Illuminate\Database\Eloquent\SoftDeletes;

use Datatables;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;

use App\FwGroup;
use App\FwRuleException;
use App\FwLayerException;

use JWTAuth;

class NetworkController extends Controller{

   public function createGroup($token){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session'))
         $sid = Session::get('sid_session');
      else $sid = $checkpoint->getLastSession();

      if($sid){

         $user = JWTAuth::toUser($token);

         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);

         $tag = $company_data2[0]['tag'];
         $server_id = 1;

   		$arrayGroup = array(
   			0 => array(
   				'group_name' => 'CUST-'.$tag.'-NET-GROUP-IPS-ALOW',
   				'server_id' => $server_id,
   				'company_id' => $company_id,
   				'tag' => $tag,
   				'token' => $request['token']
   			),
   			1 => array(
   				'group_name' => 'CUST-'.$tag.'-NET-GROUP-IPS-WHITELIST',
   				'server_id' => $server_id,
   				'company_id' => $company_id,
   				'tag' => $tag,
   				'token' => $request['token']
   			)
   		);

         foreach($arrayGroup as $value) {
            $tag = $value['tag'];
            $group_name = $value['group_name'];
            $server_id = $value['server_id'];
            $company_id = $value['company_id'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
               CURLOPT_URL => "https://172.16.3.114/web_api/add-group",
            	CURLOPT_RETURNTRANSFER => true,
            	CURLOPT_ENCODING => "",
            	CURLOPT_MAXREDIRS => 10,
            	CURLOPT_TIMEOUT => 30,
            	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            	CURLOPT_SSL_VERIFYPEER => false,
            	CURLOPT_SSL_VERIFYHOST => false,
            	CURLOPT_CUSTOMREQUEST => "POST",
            	//CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$object_name\",\r\n  \"comments\" : \"$comment\",\r\n  \"color\" : \"$color\"\r\n}",
            	CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$group_name\",\r\n  \"tags\" : [ \"$tag\"]\r\n}",
            	CURLOPT_HTTPHEADER => array(
            		"cache-control: no-cache",
            		"content-type: application/json",
            		"X-chkp-sid: ".$sid
            	),
            ));

            $response = curl_exec($curl);
            sleep(2);
            $err = curl_error($curl);

            curl_close($curl);

            if($err){
            	return "error";
            }else{

               $result = json_decode($response, true);
               Log::info("Resultado obj 114");
     				Log::info($result);

     				if(isset($result['code'])){
     					Log::info($result['code']);

                  if($result['code'] == "err_validation_failed"){
    						return "error";
    					}
     				}else{

                  $create2 = $checkpoint2->createGroup($data);
                  sleep(2);

     					$uid = $result['uid'];

     					$new_group = New FwGroup;
     					$new_group->name = $group_name;
     					$new_group->uid = $uid;
     					$new_group->server_id = $server_ch;
     					$new_group->company_id = $company_id;
     					$new_group->tag = $tag;
     					$new_group->save();

                  if($object_new->id){
     						return "success";
                  }else return "error";
               }
            }
         }
      }else{
         return "error";
      }
   }

   public function addThreatLayer(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) {
         $sid = Session::get('sid_session');
      }else{
         $sid = $checkpoint->getLastSession();
      }

      if($sid){
         Log::info($sid);

         $user = JWTAuth::toUser($request['token']);

         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);

         $tag = $company_data2[0]['tag'];
         $name = "LAYER-CUST-".$tag;

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/add-threat-layer",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$name\"}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
			Log::info(print_r($response, true));
			sleep(2);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return "error";
			}else{

            $result = json_decode($response, true);

            if(isset($result['uid'])){
               $uid = $result['uid'];
            }else{
               $uid = 'null';
            }

            $create2 = $checkpoint2->createThreatLayer($request['token']);
            sleep(2);

            $layer = new FwLayerException;
            $layer->name = $name;
            $layer->uid = $uid;
            $layer->company_id = $company_id;
            $layer->tag = $tag;
            $layer->save();

            if($layer){
               return response()->json([
               	'success' => [
               		'message' => "Layer saved successfully",
               		'status_code' => 200
               	]
               ]);
            }else{
               return response()->json([
               	'error' => [
               		'message' => "Layer not saved in DB",
               		'status_code' => 20
               	]
               ]);
            }
         }
      }else{
         return response()->json([
         	'error' => [
         		'message' => "Session not exist",
         		'status_code' => 20
         	]
         ]);
      }
   }

   public function newObjectNetwork(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) {
         $sid = Session::get('sid_session');
      }else{
         $sid = $checkpoint->getLastSession();
      }

      if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/add-threat-rule",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"LAYER-CUST-SD2300\", \r\n  \"rule-number\" : \"1\", \r\n \"position\" : \"top\", \r\n \"name\" : \"Rule Prueba3\", \r\n \"source\" : \"CUST-DS559-NET-GROUP-1-IPS-WHITELIST\", \r\n \"destination\" : \"CUST-DS559-NET-GROUP-1-IPS-ALLOW\", \r\n  \"track\" : \"None\", \r\n \"protected-scope\" : \"Any\", \r\n  \"install-on\" : \"Policy Targets\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
			Log::info(print_r($response, true));
			sleep(2);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return "error";
			}else{

            $publish = $checkpoint->publishChanges($sid);

            if($publish == 'success'){
               return "success";
            }else{
               return "success y publish error";
            }
         }
      }else{
         return "error";
      }
   }

   public function addThreatRule(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $user = JWTAuth::toUser($request['token']);
         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);

         $tag = $company_data2[0]['tag'];

         $name = $request['name'];
         $rule_position = "top";
         $src = $request['source'];
         $dst = $request['destination'];

         $array = array("name" => $name, "rule_position" => $rule_position, "source" => $src, "destination" => $dst);

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/add-threat-rule",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"LAYER-CUST-RM688\", \r\n  \"rule-number\" : \"1\", \r\n \"position\" : \"$rule_position\", \r\n \"name\" : \"$name\", \r\n \"source\" : \"$src\", \r\n \"destination\" : \"$dst\", \r\n  \"track\" : \"None\", \r\n \"protected-scope\" : \"Any\", \r\n  \"install-on\" : \"Policy Targets\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
			Log::info(print_r($response, true));
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return "error";
			}else{

            $result = json_decode($response, true);

            if(isset($result['uid'])){
               $uid = $result['uid'];
            }else{
               $uid = 'null';
            }

            $publish = $checkpoint->publishChanges($sid);

            if($publish == 'success'){

               $create2 = $checkpoint2->createThreatRule($array);
               sleep(2);

               $rule = new FwRuleException;
               $rule->name = $name;
               $rule->uid = $uid;
               $rule->company_id = $company_id;
               $rule->tag = $tag;
               $rule->layer_id = 1;
               $rule->save();

               if($rule){
                  return response()->json([
                  	'error' => [
                  		'message' => "Rule exception save successfully",
                  		'status_code' => 200
                  	]
                  ]);
               }else{
                  return response()->json([
                  	'error' => [
                  		'message' => "Rule not save",
                  		'status_code' => 20
                  	]
                  ]);
               }
            }else{
               return response()->json([
                  'error' => [
                     'message' => "Error publish changes in checkpoint",
                     'status_code' => 20
                  ]
               ]);
            }
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "Error",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function showRulesThreat(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/show-threat-rule",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"LAYER-CUST-SD2300\", \r\n  \"name\" : \"Rule Prueba3\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
			Log::info(print_r($response, true));
			//sleep(3);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return "error";
			}else{
            $publish = $checkpoint->publishChanges($sid);

            if($publish == 'success'){
               return "success";
            }else{
               return "success y publish error";
            }
         }
      }else{
         return "error";
      }
   }

}
