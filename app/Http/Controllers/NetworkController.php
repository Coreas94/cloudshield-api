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
use App\RulesExceptionObjects;

use JWTAuth;

class NetworkController extends Controller{

   public function getRulesException(Request $request){

      $user = JWTAuth::toUser($request['token']);
      $company_id = $user['company_id'];
      $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
      $company_data2 = json_decode(json_encode($company_data), true);

      $tag = $company_data2[0]['tag'];

      $rules = FwRuleException::where('fw_rules_exception.company_id', '=', $company_id)
               ->join('fw_rules_exception_objects', 'fw_rules_exception.id', '=', 'fw_rules_exception_objects.rule_id')
               ->join('fw_layer_exception', 'fw_rules_exception.layer_id', '=', 'fw_layer_exception.id')
               ->select('fw_rules_exception.*', 'fw_rules_exception_objects.src_object', 'fw_rules_exception_objects.dst_object', 'fw_layer_exception.name AS layer_name')
               ->get();

      $rules = json_decode(json_encode($rules), true);

      if(count($rules) > 0){
         return response()->json([
            'success' => [
               'data' => $rules,
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'data' => [],
               'status_code' => 20
            ]
         ]);
      }
   }

   public function getLayersException(Request $request){

      $user = JWTAuth::toUser($request['token']);
      $company_id = $user['company_id'];
      $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
      $company_data2 = json_decode(json_encode($company_data), true);
      $tag = $company_data2[0]['tag'];

      $layer = FwLayerException::where('company_id', '=', $company_id)->get();
      $layer = json_decode(json_encode($layer), true);

      if(count($layer) > 0){
         return response()->json([
            'success' => [
               'data' => $layer,
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'data' => [],
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
				// CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"Standard Threat Prevention\" \r\n}",
            CURLOPT_POSTFIELDS => "{\r\n \"uid\" : \"b413e51e-6992-4511-ab81-ebc400bab852\", \"layer\" : \"CLUSTER-IP-REPUTATION\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
         return $response;
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

   public function showRules(){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){
         $data = "{\r\n \"name\" : \"Standard Threat Prevention\", \r\n  \"rule-uid\" : \"b413e51e-6992-4511-ab81-ebc400bab852\" \r\n}";

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
				CURLOPT_POSTFIELDS => $data,
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

   public function showLayers(){
      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/show-threat-layers",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{}",
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
            return "success";
         }
      }else{
         return "error";
      }
   }

   public function showThreatException(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/show-threat-rule-exception-rulebase",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				// CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"Standard Threat Prevention\" \r\n}",
            CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"Standard Threat Prevention\", \"rule-number\" : \"1\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
         //return $response;
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

   public function showGroups(){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/show-exception-groups",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				// CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"Standard Threat Prevention\" \r\n}",
            CURLOPT_POSTFIELDS => "{\r\n \"limit\" : \"50\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
         return $response;
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
   				'token' => $token
   			),
   			1 => array(
   				'group_name' => 'CUST-'.$tag.'-NET-GROUP-IPS-WHITELIST',
   				'server_id' => $server_id,
   				'company_id' => $company_id,
   				'tag' => $tag,
   				'token' => $token
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

                  $create2 = $checkpoint2->createGroup($token, $group_name);
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

   public function removeThreatException(Request $request){
      Log::info($request);
      //die();
      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $id_rule = $request['id'];
         $name_rule = $request['rule_name'];
         #$uid = $request['uid'];
         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/delete-threat-exception",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"$name_rule\", \r\n \"exception-group-name\" : \"Global Exceptions\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);

			Log::info(print_r($response, true));
         //return $response;
			//sleep(3);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
            return response()->json([
					'error' => [
						'message' => $err,
						'status_code' => 20
					]
				]);
			}else{
            $publish = $checkpoint->publishChanges($sid);

            if($publish == 'success'){

               $remove2 = $checkpoint2->removeThreatRule2($name_rule);
               sleep(2);

               $delete = FwRuleException::where('id', $id_rule)->delete();

               if($delete){

                  $del_obj = RulesExceptionObjects::where('rule_id', '=', $id_rule)->delete();

                  return response()->json([
                     'success' => [
                        'message' => 'Threat Exception Deleted',
                        'status_code' => 200
                     ]
                  ]);
               }
            }else{
               return response()->json([
                  'success' => [
                     'message' => 'Threat Exception Deleted',
                     'status_code' => 200
                  ]
               ]);
            }
         }
      }else{
         return response()->json([
            'error' => [
               'data' => "Error connection with checkpoint",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function setThreatException(Request $request){

      Log::info($request);

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
      else $sid = $checkpoint->getLastSession();

      if($sid){

         $type_change = $request['type'];
         $value_change = $request['new_value'];
         $old_name = $request['old_name'];
         $id_rule = $request['id_rule'];
         $uid = $request['uid'];
         $layer = $request['layer_name'];

         if($type_change == "source" || $type_change == "destination"){
            $data_field = "";
            $change = "";
				foreach($value_change as $row){
					$data_field .= "\"$row\",";
               $change = $row;
				}

				$data_field2 = substr($data_field, 0, -1);
				$data_field2 = "[".$data_field2."]";
         }else{
            $type_change = "new-name";
            $data_field2 = "\"$value_change\"";
         }

         $array = array("old_name" => $old_name, "type_change" => $type_change, "value_change" => $data_field2, "uid" => $uid, "layer_name" => $layer);
      	$curl = curl_init();

      	curl_setopt_array($curl, array(
      		CURLOPT_URL => "https://172.16.3.114/web_api/set-threat-exception",
      		CURLOPT_RETURNTRANSFER => true,
      		CURLOPT_ENCODING => "",
      		CURLOPT_MAXREDIRS => 10,
      		CURLOPT_TIMEOUT => 30,
      		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      		CURLOPT_SSL_VERIFYPEER => false,
      		CURLOPT_SSL_VERIFYHOST => false,
      		CURLOPT_CUSTOMREQUEST => "POST",
      		CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"$old_name\", \r\n \"$type_change\" : $data_field2, \r\n \"exception-group-name\" : \"Global Exceptions\" \r\n}",
      		CURLOPT_HTTPHEADER => array(
      			"cache-control: no-cache",
      			"content-type: application/json",
      			"X-chkp-sid: ".$sid
      		),
      	));

      	$response = curl_exec($curl);
         Log::info("respuesta 114");
      	Log::info(print_r($response, true));
      	$err = curl_error($curl);

      	curl_close($curl);

      	if($err){
            return response()->json([
      			'error' => [
      				'message' => $err,
      				'status_code' => 20
      			]
      		]);
      	}else{

            $publish = $checkpoint->publishChanges($sid);

            if($publish == 'success'){

         		$result = json_decode($response, true);
               $objects = RulesExceptionObjects::where('rule_id', '=', $id_rule)->get();
               $objects2 = json_decode(json_encode($objects), true);
    				$id_object = $objects2[0]['id'];

               if($type_change == "new-name"){
                  $rule = FwRuleException::find($id_rule);
                  $rule->name = $value_change;
                  $rule->save();
               }elseif($type_change == "source"){
                  $src = RulesExceptionObjects::where('id', $id_object)
                     ->update(['src_object' => $change]);

               }elseif($type_change == "destination"){
                  $dst = RulesExceptionObjects::where('id', $id_object)
                     ->update(['dst_object' => $change]);
               }

               return response()->json([
                  'success' => [
                     'message' => "Datos actualizados",
                     'status_code' => 200
                  ]
               ]);
            }else{
               return response()->json([
                  'success' => [
                     'message' => "Datos actualizados",
                     'status_code' => 200
                  ]
               ]);
            }
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "Error en la conexión con checkpoint",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function addThreatException(Request $request){

      Log::info($request);

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

         $layer_data = FwLayerException::where('company_id', '=', $company_id)->get();
         $layer = json_decode(json_encode($layer_data), true);
         $layer_name = $layer[0]['name'];
         $layer_id = $layer[0]['id'];

         $name = $request['name'];
         $rule_position = "bottom";
         $src = $request['source'];
         $dst = $request['destination'];
         $action = "Inactive";

         $data_src = "";
         $data_dst = "";
         $src1 = "";
         $dst1 = "";

         foreach($src as $row){
            $data_src .= "\"$row\",";
            $src1 = $row;
         }

         $data_src2 = substr($data_src, 0, -1);
         $data_src2 = "[".$data_src2."]";

         foreach($dst as $row){
            $data_dst .= "\"$row\",";
            $dst1 = $row;
         }

         $data_dst2 = substr($data_dst, 0, -1);
         $data_dst2 = "[".$data_dst2."]";

         $array_threat = array("token" => $request['token'], "name" => $name, "source" => $data_src2, "destination" => $data_dst2);

         $curl = curl_init();

         curl_setopt_array($curl, array(
         	CURLOPT_URL => "https://172.16.3.114/web_api/add-threat-exception",
         	CURLOPT_RETURNTRANSFER => true,
         	CURLOPT_ENCODING => "",
         	CURLOPT_MAXREDIRS => 10,
         	CURLOPT_TIMEOUT => 30,
         	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         	CURLOPT_SSL_VERIFYPEER => false,
         	CURLOPT_SSL_VERIFYHOST => false,
         	CURLOPT_CUSTOMREQUEST => "POST",
         	CURLOPT_POSTFIELDS => "{\r\n \"position\" : \"$rule_position\", \r\n \"exception-group-name\" : \"Global Exceptions\", \r\n \"name\" : \"$name\", \r\n \"source\" : $data_src2, \r\n \"destination\" : $data_dst2, \r\n  \"track\" : \"None\", \r\n  \"action\" : \"Inactive\", \r\n \"protected-scope\" : \"Any\", \r\n  \"install-on\" : \"Policy Targets\" \r\n}",
         	CURLOPT_HTTPHEADER => array(
         		"cache-control: no-cache",
         		"content-type: application/json",
         		"X-chkp-sid: ".$sid
         	),
         ));

         $response = curl_exec($curl);
         $err = curl_error($curl);

         curl_close($curl);

         if($err){
            return "error";
         }else{

            $result = json_decode($response, true);
            Log::info(print_r($result, true));

            if(isset($result['code']) && $result['code'] == "generic_err_object_not_found"){
               return response()->json([
                  'error' => [
                     'message' => $result['message'],
                     'status_code' => 20
                  ]
               ]);
            }else{
               if(isset($result['uid'])){
                  $uid = $result['uid'];

                  $publish = $checkpoint->publishChanges($sid);

                  if($publish == 'success'){

                     $create2 = $checkpoint2->addThreatException2($array_threat);
                     sleep(2);

                     $array['rule_uid'] = $uid;

                     $rule = new FwRuleException;
                     $rule->name = $name;
                     $rule->uid = $uid;
                     $rule->company_id = $company_id;
                     $rule->tag = $tag;
                     $rule->action = "Inactive";
                     $rule->layer_id = $layer_id;
                     $rule->save();

                     if($rule){

                        if($rule->id){
                        	$rule_objects = new RulesExceptionObjects;
                        	$rule_objects->rule_id = $rule->id;
                        	$rule_objects->src_object = $src1;
                        	$rule_objects->dst_object = $dst1;
                        	$rule_objects->save();

                           return response()->json([
                           	'success' => [
                           		'message' => "Rule exception save successfully",
                           		'status_code' => 200
                           	]
                           ]);
                        }else{
                           return response()->json([
                           	'success' => [
                           		'message' => "Rule exception save successfully without objects",
                           		'status_code' => 200
                           	]
                           ]);
                        }
                     }else{
                        return response()->json([
                        	'error' => [
                        		'message' => "Rule exception not save",
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
               }else{
                  $uid = 'null';
                  return response()->json([
                     'error' => [
                        'message' => "No se guardó",
                        'status_code' => 20
                     ]
                  ]);
               }
            }
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "No hay sesión con checkpoint",
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
         $user = JWTAuth::toUser($request['token']);

         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);
         $company_id = $company_data2[0]['id'];
         $tag = $company_data2[0]['tag'];
         $token_company = $company_data2[0]['token_company'];

         $server_ch = 1; //Es el id del checkpoint

         $type_object = $request['type_object'];

         if(isset($request['subnet_mask'])){
            $mask = $request['subnet_mask'];
         }else{
            $mask = "null";
         }

         if($type_object == "host"){
            $type = "add-host";
            $name_object = $request['name'];
            $subnet = $request['subnet'];
            $subnet_mask = $mask;
            $data = "{\r\n  \"name\" : \"$name_object\", \r\n  \"ip-address\" : \"$subnet\", \r\n  \"tags\" : [\"$tag\"]\r\n}";
            $object_type_id = 6;

         }else{
            $type = "add-network";
            $name_object = $request['name'];
            $subnet = $request['subnet'];
            $subnet_mask = $mask;
            $data = "{\r\n  \"name\" : \"$name_object\", \r\n  \"subnet\" : \"$subnet\", \r\n  \"subnet-mask\" : \"$subnet_mask\", \r\n  \"tags\" : [\"$tag\"]\r\n}";
            $object_type_id = 5;
         }

         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.114/web_api/".$type,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
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
            return response()->json([
            	'error' => [
            		'message' => $err,
            		'status_code' => 20
            	]
            ]);
			}else{

            $result = json_decode($response, true);
 				Log::info($result);

 				if(isset($result['code'])){
               if($result['code'] == "err_validation_failed"){
                  if(isset($result['errors'])){
                     foreach($result['errors'] as $val){
                        $msg_error = $val['message'];
                     }
                  }else{
                     $msg_error = "Error! Verifique la máscara e IP ingresada";
                  }

 						return response()->json([
 							'error' => [
                        'msg_error' => $msg_error,
 								'message' => $result['message'],
 								'status_code' => 20
 							]
 						]);
 					}elseif ($result['code'] == "generic_err_invalid_parameter") {
 					   $msg_error = $result['message'];

                  return response()->json([
 							'error' => [
                        'msg_error' => $msg_error,
 								'message' => $result['message'],
 								'status_code' => 20
 							]
 						]);
 					}
 				}else{

               $publish = $checkpoint->publishChanges($sid);

     				if($publish == 'success'){
                  $object2 = $checkpoint2->createObjectNetwork($type, $data);
                  sleep(2);

                  $uid = $result['uid'];

   					$object_new = New FwObject;
   					$object_new->name = $name_object;
   					$object_new->uid = $uid;
   					$object_new->type_object_id = $object_type_id;
   					$object_new->server_id = $server_ch;
   					$object_new->company_id = $company_id;
   					$object_new->tag = $tag;
   					$object_new->editable = 1;

   					$object_new->save();

                  if($object_new->id){
                     Log::info("Se creó el objeto checkpoint");
                     $object_id = $object_new->id;
                     $type_address_id = 7;//Pertenece a rango de ip para checkpoint

                     $addr_obj = new AddressObject;
                     $addr_obj->ip_initial = $subnet;
                     $addr_obj->ip_last = $subnet;
                     $addr_obj->object_id = $object_id;
                     $addr_obj->subnet_mask = $subnet_mask;
                     $addr_obj->type_address_id = $type_address_id;
                     $addr_obj->save();

                     if($addr_obj){
                        return response()->json([
                           'success' => [
                              'message' => "Objeto y subnet creado exitosamente ",
                              'status_code' => 200
                           ]
                        ]);
                     }else{
                        return response()->json([
                           'success' => [
                              'message' => "Objeto creado exitosamente",
                              'status_code' => 200
                           ]
                        ]);
                     }
                  }else{
                     Log::info("Error al guardar obj en la bdd!!");
                     return response()->json([
                        'error' => [
                           'message' => "El objeto no pudo ser creado",
                           'status_code' => 20
                        ]
                     ]);
                  }
               }else{
                  return response()->json([
                     'error' => [
                        'message' => "El objeto no pudo ser publicado",
                        'status_code' => 20
                     ]
                  ]);
               }
            }
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "Error en la conexión con checkpoint",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function getObjectsNetwork(Request $request){

      $user = JWTAuth::toUser($request['token']);
 		$company_id = $user['company_id'];
 		$role_user = $user->roles->first()->name;

 		if($role_user == "superadmin"){
         //Log::info("super");
 			$obj = FwObject::join('fw_companies', 'fw_objects.company_id', '=', 'fw_companies.id')
            ->join('fw_address_objects', 'fw_objects.id', '=', 'fw_address_objects.object_id')
 				->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
 				->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
 				->where('fw_objects.server_id', 1)
            ->where('fw_objects.type_object_id', 5)
 				->orWhere('fw_objects.type_object_id', 6)
 				->select('fw_objects.*', 'fw_objects.name AS short_name', 'fw_companies.name AS company', 'fw_object_types.name AS type', 'fw_servers.name AS server', 'fw_address_objects.ip_initial AS ip_address', 'fw_address_objects.subnet_mask')
 				->get();
 		}else{
         //Log::info("else");
 			$obj = FwObject::join('fw_companies', 'fw_objects.company_id', '=', 'fw_companies.id')
            ->join('fw_address_objects', 'fw_objects.id', '=', 'fw_address_objects.object_id')
 				->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
 				->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
 				->where('company_id', $company_id)
 				->where('fw_objects.server_id', 1)
            ->where('fw_objects.type_object_id', 5)
 				->orWhere('fw_objects.type_object_id', 6)
 				->select('fw_objects.*', 'fw_objects.name AS short_name', 'fw_companies.name AS company', 'fw_object_types.name AS type', 'fw_servers.name AS server', 'fw_address_objects.ip_initial AS ip_address', 'fw_address_objects.subnet_mask')
 				->get();
 		}

      $list_obj = json_decode(json_encode($obj), true);

      if(count($list_obj) > 0){
         return response()->json([
            'success' => [
               'data' => $list_obj,
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
 				'error' => [
 					'message' => "No data",
 					'status_code' => 20
 				]
 			]);
      }
   }

   public function publish2(){
      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $publish = $checkpoint->publishChanges($sid);

         if($publish == 'success'){
            return "success";
         }else{
            return "success y publish error";
         }

      }else{
         return "error";
      }
   }

   public function setObjectNetwork(Request $request){
      Log::info($request);
      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $user = JWTAuth::toUser($request['token']);

         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);
         $company_id = $company_data2[0]['id'];
         $tag = $company_data2[0]['tag'];
         $token_company = $company_data2[0]['token_company'];

         $server_ch = 1; //Es el id del checkpoint

         $type_object = $request['type_object'];

         if(isset($request['subnet_mask'])){
            $mask = $request['subnet_mask'];
         }else{
            $mask = "null";
         }

         if($type_object == "host"){
            $type = "set-host";
            $name_object = $request['name'];
            $subnet = $request['subnet'];
            $subnet_mask = $mask;
            $data = "{\r\n  \"name\" : \"$name_object\", \r\n  \"ip-address\" : \"$subnet\" \r\n}";
            $object_type_id = 6;

         }else{
            $type = "set-network";
            $name_object = $request['name'];
            $old_name = $request['old_name'];
            $subnet = $request['subnet'];
            $subnet_mask = $mask;
            $data = "{\r\n  \"name\" : \"$old_name\", \r\n  \"new-name\" : \"$name_object\",\r\n  \"subnet\" : \"$subnet\", \r\n  \"subnet-mask\" : \"$subnet_mask\" \r\n}";
            $object_type_id = 5;
         }

         $array_post = array("type" => $type, "postfield" => $data);

         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.114/web_api/".$type,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
               "cache-control: no-cache",
               "content-type: application/json",
               "X-chkp-sid: ".$sid
            ),
         ));

			$response = curl_exec($curl);
			Log::info(print_r($response, true));
			sleep(1);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
            return response()->json([
            	'error' => [
            		'message' => $err,
            		'status_code' => 20
            	]
            ]);
			}else{

            $result = json_decode($response, true);
 				Log::info($result);

 				if(isset($result['code'])){
               if($result['code'] == "err_validation_failed"){
                  if(isset($result['errors'])){
                     foreach($result['errors'] as $val){
                        $msg_error = $val['message'];
                     }
                  }else{
                     $msg_error = "Error! Verifique la máscara e IP ingresada";
                  }

 						return response()->json([
 							'error' => [
                        'msg_error' => $msg_error,
 								'message' => $result['message'],
 								'status_code' => 20
 							]
 						]);
 					}elseif ($result['code'] == "generic_err_invalid_parameter") {
 					   $msg_error = $result['message'];

                  return response()->json([
 							'error' => [
                        'msg_error' => $msg_error,
 								'message' => $result['message'],
 								'status_code' => 20
 							]
 						]);
 					}else{
                  return response()->json([
 							'error' => [
 								'message' => $result['message'],
 								'status_code' => 20
 							]
 						]);
               }
 				}else{

               $publish = $checkpoint->publishChanges($sid);

     				if($publish == 'success'){
                  $object2 = $checkpoint2->setObjectNetwork($array_post);
                  sleep(1);

                  $uid = $result['uid'];
                  $object_id = $request['id'];

                  $obj = FwObject::find($object_id);
                  $obj->name = $name_object;
                  $obj->save();

                  if($obj->id){
                     Log::info("Se creó el objeto checkpoint");

                     $addr_obj = AddressObject::where('object_id', $object_id)
                        ->update(['ip_initial' => $subnet, 'ip_last' => $subnet, 'subnet_mask' => $subnet_mask]);

                     if($addr_obj){
                        return response()->json([
                           'success' => [
                              'message' => "Objeto y subnet editado exitosamente ",
                              'status_code' => 200
                           ]
                        ]);
                     }else{
                        return response()->json([
                           'success' => [
                              'message' => "Objeto editado exitosamente",
                              'status_code' => 200
                           ]
                        ]);
                     }
                  }else{
                     Log::info("Error al editar obj en la bdd!!");
                     return response()->json([
                        'error' => [
                           'message' => "El objeto no pudo ser editado",
                           'status_code' => 20
                        ]
                     ]);
                  }
               }else{
                  return response()->json([
                     'error' => [
                        'message' => "El objeto no pudo ser publicado",
                        'status_code' => 20
                     ]
                  ]);
               }
            }
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "Error en la conexión con checkpoint",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function getIpsByNetwork(Request $request){
 		$object_id = $request['object_id'];
 		$ips = DB::table('fw_address_objects')
 			->join('fw_objects', 'fw_address_objects.object_id', '=', 'fw_objects.id')
 			->where('object_id', $object_id)
 			->select('fw_address_objects.*', 'fw_objects.name as objeto')
 			->get();

      $ips = json_decode(json_encode($ips), true);
      //Log::info($ips);
      $testarray = [];
      foreach ($ips as $key => $value) {
         $rango = $value['ip_initial'].'-'.$value['ip_last'];

         $test = Range::parse($rango)->contains(new IP('1.1.1.1'));

         if($test){
            unset($ips[$key]);
         }else{
            array_push($testarray, $value);
         }
      }

 		return response()->json([
 			'data' => $testarray,
 			'object_id' => $object_id
 		]);
   }

   public function exceptiontest(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/show-threat-exception",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				// CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"Standard Threat Prevention\" \r\n}",
            CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"pruebaExc294\", \"rule-number\" : \"1\", \r\n \"layer\" : \"LAYER-CUST-RM688\" \r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
         return $response;
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

   public function removeObjectNetwork(Request $request){

      Log::info($request);

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      $type_object = $request['type_object'];
      $object_id = $request['object_id'];

      if($type_object == "host"){
      	$type = "delete-host";
      	$name_object = $request['object_name'];
      	$data = "{\r\n  \"name\" : \"$name_object\"\r\n}";
      }else{
         $type = "delete-network";
         $name_object = $request['object_name'];
         $data = "{\r\n  \"name\" : \"$name_object\"\r\n}";
      }

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
      else $sid = $checkpoint->getLastSession();

      if($sid){

         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.114/web_api/".$type,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
               "cache-control: no-cache",
               "content-type: application/json",
               "X-chkp-sid: ".$sid
            ),
         ));

      	$response = curl_exec($curl);
      	Log::info(print_r($response, true));
      	sleep(1);
      	$err = curl_error($curl);

      	curl_close($curl);

      	if($err){
            return response()->json([
            	'error' => [
            		'message' => $err,
            		'status_code' => 20
            	]
            ]);
      	}else{

            $result = json_decode($response, true);
            Log::info($result);

            if(isset($result['code'])){
            	return response()->json([
            		'error' => [
            			'message' => $result['message'],
            			'status_code' => 20
            		]
            	]);
            }else{

               $publish = $checkpoint->publishChanges($sid);

               if($publish == 'success'){

               	$obj = FwObject::find($object_id);
               	$obj->delete();

                  if($obj){
                     return response()->json([
                        'success' => [
                           'message' => 'Objeto eliminado',
                           'status_code' => 200
                        ]
                     ]);
                  }else{
                     return response()->json([
                        'error' => [
                           'message' => 'error al eliminar el objeto de la bdd',
                           'status_code' => 20
                        ]
                     ]);
                  }
               }else{

                  return response()->json([
                     'error' => [
                        'message' => 'No se eliminó el objeto',
                        'status_code' => 20
                     ]
                  ]);
               }
            }
      	}
      }else{
         return response()->json([
            'error' => [
               'message' => 'Error en la conexión a checkpoint',
               'status_code' => 20
            ]
         ]);
      }
   }

}
