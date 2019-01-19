<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use phpseclib\Net\SFTP;
use App\Jobs\senderEmailIp;
use Mail;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

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

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;

class CheckPointFunctionController extends Controller
{

   public function __construct(){
      //Log::info(Session::get('sid_session_2'));
 		Session::forget('sid_session_2');
 		$evaluate = "";
  	}

   public function servers(){
      $server = FwServer::where('type_id', 1)->get();
      $server->transform(function($item){
         $collect = [
				'id' => $item->id,
	         //'description' => $item->description,
            'name' => $item->name,
            'url' => $item->url,
            'user' => $item->user,
            'password' => $item->password,
         	//'key' => $item->server->key,
            //'type' => $item->server->type_server->name
         ];
         return $collect;
      });
      return $server;
   }

   public function loginCheckpoint(){
      $servers = self::servers();
      $user = $servers[1]['user'];
      $password = $servers[1]['password'];

      $curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://172.16.3.118/web_api/login",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_POSTFIELDS => "{\r\n  \"user\" : \"$user\",\r\n  \"password\" : \"$password\"\r\n}",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json"
			),
		));

		$response = curl_exec($curl);
		Log::info(print_r($response, true));
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			return response()->json([
				'error' => [
					'message' => $err,
					'status_code' => 20
				]
			]);
		}else{
			$result = json_decode($response, true);
			#log::info($result);
         $sid = $result['sid'];
         Session::put('sid_session_2', $sid);
         return $sid;
		}
   }

   public function getLastSession(){
 		$servers = self::servers();
 		$user = $servers[1]['user'];
 		$password = $servers[1]['password'];

      $curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://172.16.3.118/web_api/login",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "{\r\n  \"user\" : \"$user\",\r\n  \"password\" : \"$password\",\r\n  \"continue-last-session\" : true\r\n}",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			$sid = $this->loginCheckpoint();
		} else {
			$result = json_decode($response, true);

			if(isset($result['code'])){
				Log::info($result['code']);
				if($result['code'] == "err_login_failed_more_than_one_opened_session"){
					$sid = $this->loginCheckpoint();
				}else{
					$sid = $this->loginCheckpoint();
				}
			}else{
				$sid = $result['sid'];
			}
		}

		Session::put('sid_session_2', $sid);
		return $sid;
  	}

   public function publishChanges($sid){
      $curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://172.16.3.118/web_api/publish",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "{ }",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json",
				"X-chkp-sid: ".$sid
			),
		));

		$response = curl_exec($curl);
		#Log::info(print_r($response, true));
		$err = curl_error($curl);

		curl_close($curl);

		if($err){
			return "error";
		} else {
			return "success";
		}
  	}

   public function discardChanges(){
 		$sid = Session::get('sid_session_2');

      return Control::curl("172.16.3.118")
         ->is('discard')
         ->sid($sid)
         ->eCurl();
   }

   public function installPolicy(){
      if(Session::has('sid_session_2'))
         $sid = Session::get('sid_session_2');
      else $sid = $this->getLastSession();

      if($sid){
         Control::curl("172.16.3.118")
         ->is('install-policy')
         ->config([
            'policy-package' => 'standard',
            'access' => true,
            'threat-prevention' => true,
            'targets' => ['CLUSTER-IP-REPUTATION']
         ])
         ->sid($sid)
         ->eCurl(function($response){
            $this->output = $response;
            $this->typeResponseCurl = 1;
         }, function($error){
            $this->output = $error;
            $this->typeResponseCurl = 0;
         });

         if(!$this->typeResponseCurl){
            //Log::info("error en el curl");
            return "error";
         }else{
            $resp = json_decode($this->output, true);
            Log::info("RESPUESTA INSTALL");
            Log::info($resp);
            if(isset($resp['task-id'])){
               $task = $resp['task-id'];
               $result_task = $this->showTask($task);

               Log::info("RESULT TASK");
               Log::info($result_task);

               foreach($result_task['tasks'] as $key => $value){
                  if($value['status'] == "succeeded")
                     return "success";
                  else
                     return "error";
               }
            }else return "error";
         }
      }else return "error con el sid";
   }

   public function showTask($task_id){
  		$percentage = 0;
  		if(Session::has('sid_session_2'))
  			$sid = Session::get('sid_session_2');
  		else $sid = $this->getLastSession();

      $response = "";
  		while($percentage != 100) {
         Control::curl("172.16.3.118")
         ->is('show-task')
         ->config([
            'task-id' => $task_id
         ])
         ->sid($sid)
         ->eCurl(function($response){
            $this->output = $response;
            $this->typeResponseCurl = 1;
         }, function($error){
            $this->output = $error;
            $this->typeResponseCurl = 0;
         });

         if($this->typeResponseCurl){
            $response = json_decode($this->output, true);
     			foreach($response['tasks'] as $row)
     				$percentage = $row['progress-percentage'];
         }
  		}
  		return $response;
  	}

   public function createSections2($tag, $company_id){
      Log::info("llega al section2");
      if(Session::has('sid_session_2')){
         Log::info("Existe sesion 118");
         $sid = Session::get('sid_session_2');
      }else {
         Log::info("No existe sesion 118");
         $sid = $this->getLastSession();
      }

      if($sid){
         $name_section = 'CUST-'.$tag;
         #$rule_name = $request['rule_name'];
         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.118/web_api/add-access-section",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            //CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"Network\",\r\n  \r\n \"name\" : \"$name_section\"\r\n  \r\n  \"position\" : {\r\n    \"below\" : \"DATACENTER\"} \r\n}",
            CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"Network\",\r\n  \r\n \"name\" : \"$name_section\"\r\n, \"position\" : {\r\n \"below\" : \"DATACENTER NETWORKS\"} \r\n }",
            CURLOPT_HTTPHEADER => array(
               "cache-control: no-cache",
               "content-type: application/json",
               "X-chkp-sid: ".$sid
            ),
         ));

         $response = curl_exec($curl);

         $err = curl_error($curl);

         curl_close($curl);

         $result = json_decode($response, true);
         Log::info("CREACION DE SECTION 118");
         Log::info(print_r($result, true));

         if($err){
            return "error";
         }else{
            $publish = $this->publishChanges($sid);

            if($publish == 'success'){
               return "success";
            }else{
               return "error";
            }
         }
      }else{
         Log::info("Retorna error 118");
         return "error";
      }
   }

   public function addObjectsToRule2($request){
      Log::info("Llega al addObjectsToRule2");
      Log::info($request);
 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

 		if($sid){
			$uid_rule = $request['uid_rule'];
         $name_rule = $request['name_rule'];
			$field_change = $request['field_change'];
			$new_changes = $request['field_change_value'];

			if($field_change == "action"){
				$data_field2 = "\"$new_changes\"";
			}else{
				$fields = explode(",", $new_changes);

				$data_field = "";
				foreach($fields as $row){
					$data_field .= "\"$row\",";
				}

				$data_field2 = substr($data_field, 0, -1);
				$data_field2 = "[".$data_field2."]";
			}

         $curl = curl_init();

			curl_setopt_array($curl, array(
			  	CURLOPT_URL => "https://172.16.3.118/web_api/set-access-rule",
			  	CURLOPT_RETURNTRANSFER => true,
			  	CURLOPT_ENCODING => "",
			  	CURLOPT_MAXREDIRS => 10,
			  	CURLOPT_TIMEOUT => 30,
			  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  	CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"$name_rule\",\r\n \"layer\" : \"Network\",\r\n  \"$field_change\" : $data_field2 \r\n}",
			  	CURLOPT_HTTPHEADER => array(
			    	"cache-control: no-cache",
			    	"content-type: application/json",
			    	"x-chkp-sid: ".$sid
			  	),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				return response()->json([
					'error' => [
						'message' => $err,
						'status_code' => 20
					]
				]);
			}else{
  				$result = json_decode($response, true);
            Log::info("RESULT addObjectsToRule2 118");
  				Log::info(print_r($result, true));
  				$publish = $this->publishChanges($sid);
  				if($publish == "success"){
               return "success";
            }
  				else{
               return "error";
            }
			}
 		}else {
         return "error";
      }
  	}

   public function disableRule2($request){
 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

 		if($sid){
			$uid_rule = $request['uid'];
         $name_rule = $request['name'];
			$status = $request['enabled'];

			if($status == true) $value_enable = 'false';
			else $value_enable = 'true';

         Control::curl("172.16.3.118")
         ->is("set-access-rule")
         ->config([
            'name' => $name_rule,
            'layer' => 'Network',
            'enabled' => $value_enable
         ])
         ->sid($sid)
         ->eCurl(function($response){
            $this->typeResponseCurl = 1;
            $this->output = $response;
         }, function($error){
            $this->typeResponseCurl = 0;
            $this->output = $error;
         });

			if(!$this->typeResponseCurl){
            return "error";
         }else{
  				$publish = $this->publishChanges($sid);

            if($publish == "success"){
               return "success";
            }else{
               return "error";
            }
			}
 		}else{
         return "error";
      }
   }

   public function removeRule2($request){
      Log::info("Llega al remove 118");
 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

 		if($sid){

         $uid_rule = $request['uid'];
         $name_rule = $request['name'];
         Control::curl("172.16.3.118")
         ->is("delete-access-rule")
         ->config([
            'name' => $name_rule,
            'layer' => 'Network'
         ])
         ->sid($sid)
         ->eCurl(function($response){
            $this->typeResponseCurl = 1;
            $this->output = $response;
         }, function($error){
            $this->typeResponseCurl = 0;
            $this->output = $error;
         });
         Log::info($this->output);
 			if(!$this->typeResponseCurl){
            return response()->json([
   				'error' => [
   					'message' => $this->output,
   					'status_code' => 20
   				]
            ]);
         }else{
 				$publish = $this->publishChanges($sid);

 				if($publish == "success"){
					//$install = $this->installPolicy();
					return "success";
 				}else{
               return "error";
            }
 			}
 		}else{
         return "error";
      }
  	}

   public function createDynamicObject2($request){

      if(Session::has('sid_session_2'))
         $sid = Session::get('sid_session_2');
      else $sid = $this->getLastSession();

      if($sid){
         Log::info("Existe sid en obj 118");
         $evaluate;
         $server_ch = 1; //Es el id del checkpoint
         $new_object_name = $request['object_name'];
         //$tag = $request['tag'];
         $comment = "Prueba code";
         $company_id = $request['company_id'];

         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);
         $tag = $company_data2[0]['tag'];

         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.118/web_api/add-dynamic-object",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$new_object_name\",\r\n  \"comments\" : \"$comment\",\r\n  \"tags\" : [ \"$tag\"]\r\n}",
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
 				Log::info("RESULT: 118****");
 				Log::info($result);

            $publish = $this->publishChanges($sid);

 				if($publish == "success"){
					return "success";
 				}else{
               return "error";
            }
         }
      }else{
         return "error";
      }
   }

   public function removeObject2($request){

 		$object_id = $request['object_id'];
 		$object_name = $request['object_name'];

 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

      if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
			  	CURLOPT_URL => "https://172.16.3.118/web_api/delete-dynamic-object",
			  	CURLOPT_RETURNTRANSFER => true,
			  	CURLOPT_ENCODING => "",
			  	CURLOPT_MAXREDIRS => 10,
			  	CURLOPT_TIMEOUT => 30,
			  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  	CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"$object_name\"\r\n}",
			  	CURLOPT_HTTPHEADER => array(
			    	"cache-control: no-cache",
			    	"content-type: application/json",
			    	"postman-token: 67baa239-ddc9-c7a4-fece-5a05f2396e38",
			    	"x-chkp-sid: ".$sid
			  	),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return "error";
			}else{

 				$result = json_decode($response, true);
            Log::info($result);

 				if(isset($result['code'])){
 					return "error";
 				}else{
 					$publish = $this->publishChanges($sid);

 					if($publish == "success"){
                  return "success";

 					}else{
 						return "error";
 					}
 				}
 			}
 		}else{
 			return "error";
 		}
   }

   public function createTag2($tag){

 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

      if($sid){

         $curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.118/web_api/add-tag",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"Tag Name\",\r\n  \"tags\" : [ \"$tag\"]\r\n}",
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
 				$publish = $this->publishChanges($sid);
 				if($publish == "success"){
 					return "success";
 				}else{
 					return "error";
 				}
 			}
 		}else{
 			return "error";
 		}
   }

   public function moveRule2($request){
   	if(Session::has('sid_session_2'))
   		$sid = Session::get('sid_session_2');
   	else $sid = $this->getLastSession();

  		if($sid){

  			$uid_rule = $request['uid'];
  			$name_rule = $request['name'];
  			$position = $request['position'];
  			$rule_change = $request['name_change'];

         Control::curl("172.16.3.118")
         ->is("set-access-rule")
         ->config([
            'name' => $rule_change,
            'layer' => 'Network',
            'new-position' => [
               $position => $name_rule
            ]
         ])
         ->sid($sid)
         ->eCurl(function($response){
            $this->output = $response;
            $this->typeResponseCurl = 1;
         }, function($error){
            $this->output = $error;
            $this->typeResponseCurl = 0;
         });
         Log::info("INFO move RUle 118");
         Log::info($this->output);
  			if(!$this->typeResponseCurl){
  				return response()->json([
  					'error' => [
  						'message' => $this->output,
  						'status_code' => 20
  					]
  				]);
  			}else{

  				$result = json_decode($this->output, true);

  				if(isset($result['code'])){
  					Log::info($result['code']);
  					if($result['code'] == "err_rulebase_invalid_operation"){
  						return "error";
  					}
  				}else{

  					$publish = $this->publishChanges($sid);

  					if($publish == "success"){
  						return "success";
  					}else{
  						return "error";
  					}
  				}
  			}
  		}else{
  			return "error";
  		}
  	}

   public function addRules2($data){

 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

 		if($sid){

 			$section = $data['section'];
 			$rule_name = $data['name'];
 			$src = $data['source'];
 			$dst = $data['destination'];
 			$vpn = $data['vpn'];
 			$action = $data['action'];
 			$company_id = $data['company_id'];
 			$tag = $data['tag'];
 			$section_id = $data['section_id'];

         $curl = curl_init();

         curl_setopt_array($curl, array(
         	CURLOPT_URL => "https://172.16.3.118/web_api/add-access-rule",
         	CURLOPT_RETURNTRANSFER => true,
         	CURLOPT_ENCODING => "",
         	CURLOPT_MAXREDIRS => 10,
         	CURLOPT_TIMEOUT => 30,
         	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         	CURLOPT_SSL_VERIFYPEER => false,
         	CURLOPT_SSL_VERIFYHOST => false,
         	CURLOPT_CUSTOMREQUEST => "POST",
         	CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"Network\", \r\n  \"ignore-warnings\" : \"true\", \r\n  \"position\" : {\r\n \"bottom\" : \"$section\" \r\n },\r\n  \"name\" : \"$rule_name\",\r\n \"source\" : \"$src\", \r\n\t\"destination\" : \"$dst\", \r\n\t\"action\" : \"$action\", \r\n  \"vpn\" : \"Any\"\r\n, \r\n  \"comments\" : \"no-editable\"\r\n}",
         	CURLOPT_HTTPHEADER => array(
         		"cache-control: no-cache",
         		"content-type: application/json",
         		"X-chkp-sid: ".$sid
         	),
         ));

         $response = curl_exec($curl);
         Log::info("RESPUESTA ADD RULES 118-----------------------------------");

         $err = curl_error($curl);

         curl_close($curl);

         if($err){
            Log::info($err);
         	return "error";
         }else{
            $result = json_decode($response, true);
            Log::info(print_r($result, true));

 				$publish2 = $this->publishChanges($sid);

 				if($publish2 == 'success'){
 					return "success";
 				}else{
 					return "error";
 				}
 			}
 		}else{
 			return "error";
 		}
   }

   public function addObjectCompany2($data){
		if(Session::has('sid_session_2'))
			$sid = Session::get('sid_session_2');
  		else $sid = $this->getLastSession();

  		if($sid){

         $server_ch = 1; //Es el id del checkpoint
  			$new_object_name = $data['object_name'];
  			$tag = $data['tag'];
  			$comment = "Prueba code";

         if(isset($row['ips_assigned'])){
            foreach($row['ips_assigned'] as $value){
      			$ip_initial = $value['ip_init'];
      			$ip_last = $value['ip_last'];
            }
         }else{
            $ip_initial = '1.1.1.1';
     			$ip_last = '1.1.1.1';
         }

  			$company_id = $data['company_id'];

         $curl = curl_init();

         curl_setopt_array($curl, array(
         	CURLOPT_URL => "https://172.16.3.118/web_api/add-dynamic-object",
         	CURLOPT_RETURNTRANSFER => true,
         	CURLOPT_ENCODING => "",
         	CURLOPT_MAXREDIRS => 10,
         	CURLOPT_TIMEOUT => 30,
         	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         	CURLOPT_SSL_VERIFYPEER => false,
         	CURLOPT_SSL_VERIFYHOST => false,
         	CURLOPT_CUSTOMREQUEST => "POST",
         	//CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$object_name\",\r\n  \"comments\" : \"$comment\",\r\n  \"color\" : \"$color\"\r\n}",
         	CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$new_object_name\",\r\n  \"comments\" : \"$comment\",\r\n  \"tags\" : [ \"$tag\"]\r\n}",
         	CURLOPT_HTTPHEADER => array(
         		"cache-control: no-cache",
         		"content-type: application/json",
         		"X-chkp-sid: ".$sid
         	),
         ));

         $response = curl_exec($curl);
         sleep(3);
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
            Log::info("Resutlado obj 118");
  				Log::info($result);

  				if(isset($result['code'])){
  					return "error";
  				}else{
               return "success";
            }
         }
      }else{
         return "error";
      }
   }

   public function addNewRule2($request){
      Log::info("Llega al addNewRule2");
      if(Session::has('sid_session_2'))
         $sid = Session::get('sid_session_2');
 		else
         $sid = $this->getLastSession();

 		if($sid){
         Log::info("existe sid addNewRule2");
 			$rule_name = $request['name'];
 			$src = $request['source'];
 			$dst = $request['destination'];
 			$vpn = $request['vpn'];
 			$action = $request['action'];
 			$company_id = $request['company_id'];

 			$section_company = FwSectionAccess::where('company_id', $company_id)->get();

 			//CREAR UNA NUEVA SECCIÓN CON EL TAG ELEGIDO SI NO EXISTE
 			/*if(count($section_company) == 0){
 			}else{
 			}*/

 			Log::info($section_company);
 			$name_section = $section_company[0]['name'];
 			$tag = $section_company[0]['tag'];
 			$section_id = $section_company[0]['id'];

 			$rule_name = "CUST-".$tag."-$rule_name";

         Control::curl("172.16.3.118")
         ->is("add-access-rule")
         ->config([
            'layer' => "Network",
            'ignore-warnings' => true,
            'position' => [
               'bottom' => $name_section
            ],
            'name' => $rule_name,
            'source' => $src,
            'destination' => $dst,
            'action' => $action,
            'vpn' => 'Any',
            'comments' => 'editable'
         ])
         ->sid($sid)
         ->eCurl(function($response){
            $this->output = $response;
            $this->typeResponseCurl = 1;
         }, function($error){
            $this->output = $error;
            $this->typeResponseCurl = 0;
         });

 			if(!$this->typeResponseCurl){
 				return "error";
 			}else{
            $rsp = $this->output;
 				$publish2 = $this->publishChanges($sid);

 				if($publish2 == 'success'){

 					$result = json_decode($rsp, true);
 					Log::info($result);

 					if(isset($result['code'])){
 						Log::info($result['code']);
                  Log::info("Existe error en checkpoint");
 						return "error";
 					}else{
                  Log::info("SUCCESS RULEEE");
                  return "success";
 						/*$install = $this->installPolicy();
                  Log::info($install);

 						if($rule->id && $install == "success"){
                     Log::info("regla creada");

 							return "success";
 						}else{
 							return "error";
 						}*/
 					}
 				}else{
 					return "error";
 				}
 			}
 		}else{
         Log::info("no existe sid addNewRule2");
 			return "error";
 		}
   }

   public function createGroup($data){

      if(Session::has('sid_session_2'))
         $sid = Session::get('sid_session_2');
      else $sid = $this->getLastSession();

      if($sid){

         $tag = $data['tag'];
   		$company_id = $data['company_id'];
         $group_name = $data['group_name'];
         $server_id = 1;
   		$token = $data['token'];

         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.118/web_api/add-group",
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
               if($result['code'] == "err_validation_failed"){
                  return "error";
               }
            }else{
               return "success";
            }
         }
      }else{
         return "error";
      }
   }

   public function createThreatLayer($token){

      if(Session::has('sid_session_2'))
         $sid = Session::get('sid_session_2');
      else $sid = $this->getLastSession();

      if($sid){

         $user = JWTAuth::toUser($token);

         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);

         $tag = $company_data2[0]['tag'];
         $name = "LAYER-CUST-".$tag;

         $curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://172.16.3.118/web_api/add-threat-layer",
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
         sleep(2);
         $err = curl_error($curl);

         curl_close($curl);

         if($err){
            return "error";
         }else{

            $result = json_decode($response, true);
            Log::info("Resultado obj 118");
            Log::info($result);

            if(isset($result['code'])){
               if($result['code'] == "err_validation_failed"){
                  return "error";
               }
            }else{
               return "success";
            }
         }
      }else{
         return "error";
      }
   }



   public function createThreatRule($data){

      if(Session::has('sid_session_2')) $sid = Session::get('sid_session_2');
 		else $sid = $this->getLastSession();

 		if($sid){

         $name = $data['name'];
         $rule_position = "top";
         $src = $data['source'];
         $dst = $data['destination'];

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.118/web_api/add-threat-rule",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\r\n  \"layer\" : \"LAYER-CUST-SD2300\", \r\n  \"rule-number\" : \"1\", \r\n \"position\" : \"$rule_position\", \r\n \"name\" : \"$name\", \r\n \"source\" : \"$src\", \r\n \"destination\" : \"$dst\", \r\n  \"track\" : \"None\", \r\n \"protected-scope\" : \"Any\", \r\n  \"install-on\" : \"Policy Targets\" \r\n}",
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
            return "success";
         }
      }else{
         return "error";
      }
   }


}
