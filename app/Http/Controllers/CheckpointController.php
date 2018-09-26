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
use App\Http\Controllers\CheckPointFunctionController;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;

class CheckpointController extends Controller
{
   private $output = "";
   private $typeResponseCurl = 1;

   //Comienza desde aqui
   public function __construct(){
 		Session::forget('sid_session');
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
      $user = $servers[0]['user'];
      $password = $servers[0]['password'];

      Control::curl("172.16.3.114")
      ->config([
         'user' => $user,
         'password' => $password
      ])
      ->eCurl(function($response){
         $this->output = $response;
         $this->typeResponseCurl = 1;
      }, function($error){
         $this->output = $error;
         $this->typeResponseCurl = 0;
      });

      if (!$this->typeResponseCurl){
         return response()->json([
            'error' => [
               'message' => $this->output,
               'status_code' => 20
            ]
         ]);
      }else{
         $result = json_decode($this->output, true);
         Log::info("RESULTADO LOGIN 114");
         Log::info($result);
         $sid = $result['sid'];
         Session::put('sid_session', $sid);
         return $sid;
      }
   }

   public function getLastSession(){
 		$servers = self::servers();
 		$user = $servers[0]['user'];
 		$password = $servers[0]['password'];

      Control::curl("172.16.3.114")
      ->config([
         'user' => $user,
         'password' => $password,
         'continue-last-session' => true
      ])->eCurl(function($response){
         $this->output = $response;
         $this->typeResponseCurl = 1;
      }, function($error){
         $this->output = $error;
         $this->typeResponseCurl = 0;
      });

 		if (!$this->typeResponseCurl) $sid = $this->loginCheckpoint();
 		else {
			$result = json_decode($this->output, true);
			if(isset($result['code'])){
				//Log::info($result['code']);
				if($result['code'] == "err_login_failed_more_than_one_opened_session")
					$sid = $this->loginCheckpoint();
				else
					$sid = $this->loginCheckpoint();
			}else $sid = $result['sid'];
 		}
 		Session::put('sid_session', $sid);
 		return $sid;
  	}

   public function publishChanges($sid){
      $curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://172.16.3.114/web_api/publish",
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
 		$sid = Session::get('sid_session');

      return Control::curl("172.16.3.114")
         ->is('discard')
         ->sid($sid)
         ->eCurl();
   }

   public function installPolicy(){

      Log::info("llega al install");

      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session'))
         $sid = Session::get('sid_session');
      else $sid = $this->getLastSession();

      if($sid){
         Control::curl("172.16.3.114")
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

               $installCh2 = $checkpoint2->installPolicy();

               Log::info("RESULT TASK");
               Log::info($result_task);

               $textResp = "";

               foreach($result_task['tasks'] as $key => $value){
                  if($value['status'] == "succeeded")
                     $textResp = "success";
                     //return "success";
                  else
                     $textResp = "error";
                     //return "error";
               }

               if($textResp == "success" && $installCh2 == "success"){
                  Log::info("Políticas instaladas correctamente en ambos checkpoint");
                  return "Políticas instaladas correctamente en ambos checkpoint";
               }elseif ($textResp == "success" && $installCh2 != "success") {
                  Log::info("Políticas instaladas correctamente solo en el checkpoint 114");
                  return "Políticas instaladas correctamente solo en el checkpoint 114";
               }elseif ($textResp != "success" && $installCh2 == "success") {
                  Log::info("Políticas instaladas correctamente solo en el checkpoint 118");
                  return "Políticas instaladas correctamente solo en el checkpoint 118";
               }else{
                  Log::info("Politicas no instaladas");
                  return "Politicas no instaladas";
               }
            }else return "error";
         }
      }else return "error con el sid";
   }

   public function showTask($task_id){
  		$percentage = 0;
  		if(Session::has('sid_session'))
  			$sid = Session::get('sid_session');
  		else $sid = $this->getLastSession();

      $response = "";
  		while($percentage != 100) {
         Control::curl("172.16.3.114")
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

   public function getObjectsByCompany(Request $request){
 		$user = JWTAuth::toUser($request['token']);
 		$company_id = $user['company_id'];
 		$role_user = $user->roles->first()->name;
  	} //?

   public function getIpsByObject(Request $request){
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
            "no existe la ip";
         }
      }

 		return response()->json([
 			'data' => $ips,
 			'object_id' => $object_id
 		]);
   }

   public function assignIpObject(Request $request){
 		$object_id = $request['object_id'];
      $evaluate = "";

 		$object = DB::table('fw_objects')->where('id', $object_id)->get();
 		$object = json_decode(json_encode($object), true);

 		$object_name = $object[0]['name'];
 		$ip_initial = $request['ip_initial'];
 		$ip_last = $request['ip_last'];

	   $type_address_id = 7;//Pertenece a rango de ip para checkpoint

      Log::info("llega");

      /*$curl = curl_init();

      curl_setopt_array($curl, array(
         CURLOPT_URL => "http://localhost:3500/new_object",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => "",
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_SSL_VERIFYPEER => false,
         CURLOPT_SSL_VERIFYHOST => false,
         CURLOPT_CUSTOMREQUEST => "POST",
         //CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\"}",
         CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\",, \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\r\n}",
         CURLOPT_HTTPHEADER => array(
            "content-type: application/json",
         ),
      ));

      $response = curl_exec($curl);
      Log::info(print_r($response, true));
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
         return response()->json([
            'error' => [
              'message' => "El objeto se creó pero no las Ips",
              'status_code' => 20
            ]
         ]);
      } else {*/

      $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";


		\SSH::into('checkpoint')->run($ssh_command, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

      $evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false) {
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

		sleep(2);

		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
			Log::info($line2.PHP_EOL);
			$evaluate = $line2.PHP_EOL;
		});

      $evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false) {
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
				Log::info($line2.PHP_EOL);
				$evaluate = $line2.PHP_EOL;
			});
		}

      sleep(2);

		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
			Log::info($line3.PHP_EOL);
			$evaluate = $line3.PHP_EOL;
		});

      $evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false) {
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
				Log::info($line3.PHP_EOL);
				$evaluate = $line3.PHP_EOL;
			});
		}

      sleep(2);

		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
			Log::info($line4.PHP_EOL);
			$evaluate = $line4.PHP_EOL;
		});

      $evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false) {
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
				Log::info($line4.PHP_EOL);
				$evaluate = $line4.PHP_EOL;
			});
		}

      sleep(2);

      $addr_obj = new AddressObject;
 		$addr_obj->ip_initial = $ip_initial;
 		$addr_obj->ip_last = $ip_last;
 		$addr_obj->object_id = $object_id;
 		$addr_obj->type_address_id = $type_address_id;
 		$addr_obj->save();

 		if($addr_obj){
			$bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $object_id, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(),
			'updated_at' => \Carbon\Carbon::now()]);

			if($bd_ips_check){
  				//Log::info("Se guardo el rango");
  				return response()->json([
  					'success' => [
  						'message' => "¡IP guardada exitosamente!",
  						'status_code' => 200
  					]
  				]);
			}else{
  				return response()->json([
  					'error' => [
  						'message' => 'error al guardar la IP',
  						'status_code' => 20
  					]
  				]);
			}
 		}else{
			return response()->json([
				'error' => [
					'message' => 'error al guardar la IP',
					'status_code' => 20
				]
			]);
 		}
      //}
  	}

   public function orderObjectsBD(){
 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else
 			$sid = $this->getLastSession();

 		if($sid){
         Control::curl("172.16.3.114")
         ->sid($sid)
         ->is('show-dynamic-objects')
         ->eCurl(function($response){
            $this->output = $response;
            $this->typeResponseCurl = 1;
         }, function($error){
            $this->output = $error;
            $this->typeResponseCurl = 0;
         });

 			if(!$this->typeResponseCurl){
				return response()->json([
					'error' => [
						'message' => $this->output,
						'status_code' => 20
					]
				]);
 			}else{
            Log::info($this->output);
            return;
				$result = json_decode($this->output, true);
				$arr = [];
				$i = 0;
				$servers = self::servers();
				$object_type = 4; //Es object dynamic

				foreach($result['objects'] as $key => $value){

					$arr[$i]['name'] = $value['name'];
					$arr[$i]['uid'] = $value['uid'];
					$arr[$i]['type_object_id'] = $object_type;
					$arr[$i]['server_id'] = $servers[0]['id'];
					$arr[$i]['company_id'] = 0;
					$arr[$i]['created_at'] = \Carbon\Carbon::now();
					$arr[$i]['updated_at'] = \Carbon\Carbon::now();
					$i++;
				}

				$insert = DB::table('fw_objects')->insert($arr);
				if($insert) return response()->json([
  						'success' => [
  							'data' => "Success",
  							'status_code' => 200
  						]
  					]);
				else return response()->json([
						'error' => [
							'message' => 'error al guardar los objetos',
							'status_code' => 20
						]
					]);
 			}
 		}else return response()->json([
			'error' => [
				'message' => 'error con la sesión al checkpoint',
				'status_code' => 20
			]
	   ]);
  	}

   public function createSections($tag, $company_id){
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();
    		if($sid){
   			$name_section = 'CUST-'.$tag;
   			#$rule_name = $request['rule_name'];
            $curl = curl_init();

	         curl_setopt_array($curl, array(
   				CURLOPT_URL => "https://172.16.3.114/web_api/add-access-section",
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
   			//Log::info("RESPONSE SECTION");
   			//Log::info(print_r($response, true));
   			sleep(3);
   			$err = curl_error($curl);

   			curl_close($curl);

   			if($err){
   				return "error";
   			}else{
     				$publish = $this->publishChanges($sid);

     				if($publish == 'success'){

                  $section = new FwSectionAccess;
                  $section->name = $name_section;
                  $section->company_id = $company_id;
                  $section->tag = $tag;
                  $section->save();

                  if($section->id){
                  	return [$section->id, $name_section];
                  }else{
                  	return [1, $name_section];
                  }
     				}else{
     					return "error";
     				}
   			}
    		}else{
   			return "error";
    		}
  	}

   public function addObjectsToRule(Request $request){

      Log::info($request);
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){
			$uid_rule = $request['uid_rule'];
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
			  	CURLOPT_URL => "https://172.16.3.114/web_api/set-access-rule",
			  	CURLOPT_RETURNTRANSFER => true,
			  	CURLOPT_ENCODING => "",
			  	CURLOPT_MAXREDIRS => 10,
			  	CURLOPT_TIMEOUT => 30,
			  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  	CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_POSTFIELDS => "{\r\n \"uid\" : \"$uid_rule\",\r\n \"layer\" : \"Network\",\r\n  \"$field_change\" : $data_field2 \r\n}",
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

			if ($err) {
				return response()->json([
					'error' => [
						'message' => $err,
						'status_code' => 20
					]
				]);
			}else{
  				$result = json_decode($response, true);
  				Log::info(print_r($result, true));

            $resp_obj = $checkpoint2->addObjectsToRule2($request);
            sleep(2);

  				$publish = $this->publishChanges($sid);
  				if($publish == "success"){
               return response()->json([
                  'message' => "success",
               ]);
            }
  				else{
               return response()->json([
   		         'message' => "error",
   				]);
            }
			}
 		}else return response()->json([
			'error' => [
				'message' => "No existe sesión con el checkpoint",
				'status_code' => 20
			]
		]);
  	}

   public function saveServicesCheckpoint(Request $request){

 		$array_post = ['show-services-tcp', 'show-services-udp', 'show-services-icmp', 'show-services-icmp6', 'show-services-other', 'show-services-dce-rpc', 'show-services-rpc'];
 		$data = [];
 		$i = 0;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

    		if($sid){
    			foreach ($array_post as $value) {
               Control::curl("172.16.3.114")
               ->is($value)
               ->config([
                  'limit' => 50,
                  'offset' => 0,
                  'details-level' => 'standard'
               ])
               ->sid($sid)
               ->eCurl(function($response){
                  $this->output = $response;
                  $this->typeResponseCurl = 1;
               }, function($error){
                  $this->output = $response;
                  $this->typeResponseCurl = 0;
               });

   				if (!$this->typeResponseCurl) {
   				    return $this->output;
   				} else {
     					$result = json_decode($this->output, true);
     				  $data[$i] = $result['objects'];
   				}
   				$i++;
    			}

    			foreach ($data as $val) {
    				foreach($val as $valor){

    					$srv = new ServicesCheckpoint;
    					$srv->name = $valor['name'];
    					$srv->uid = $valor['uid'];
    					$srv->type = $valor['type'];
    					$srv->port = isset($valor['port']) ? $valor['port'] : 'N/A';
    					$srv->save();
    				}
    			}

    			return response()->json([
    				'success' => [
    					'message' => "Servicios y aplicaciones guardadas correctamente",
    					'status_code' => 200
    				]
    			]);
    		} else return response()->json([
				'error' => [
					'message' => "No existe sesión con el checkpoint",
					'status_code' => 20
				]
			]);
   }

   public function getServicesCheckpoint(Request $request){
 		//$datos = ServicesCheckpoint::get();
 		$services = DB::table('fw_services_ch')->select('fw_services_ch.*', 'fw_services_ch.name AS text')->get();

 		$arreglo = array("id" => 1, "text" => "Any", "name" => "Any");
 		array_unshift($services, $arreglo);

 		return response()->json([
 			'data' => $services,
 			'status_code' => 200
 		]);
  	}

   public function disableRule(Request $request){

      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){
			$uid_rule = $request['uid'];
			$status = $request['enabled'];

			if($status == true) $value_enable = 'false';
			else $value_enable = 'true';

         Control::curl("172.16.3.114")
         ->is("set-access-rule")
         ->config([
            'uid' => $uid_rule,
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
            return response()->json([
   				'error' => [
   					'message' => $this->output,
   					'status_code' => 20
   				]
   			]);
         }else{
  				$publish = $this->publishChanges($sid);

            if($publish == "success"){

               $disable2 = $checkpoint2->disableRule2($request);

               return response()->json([
   					'success' => [
   						'message' => "Regla deshabilitada",
   						'status_code' => 200
   					]
   				]);
            }else{
               return response()->json([
   					'error' => [
   						'message' => "No se deshabilitó la regla",
   						'status_code' => 20
   					]
   				]);
            }
			}
 		}else{
         return response()->json([
   			'error' => [
   				'message' => "No existe sesión con el checkpoint",
   				'status_code' => 20
   			]
   		]);
      }
   }

   public function removeRule(Request $request){

      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){

         $uid_rule = $request['uid'];
         Control::curl("172.16.3.114")
         ->is("delete-access-rule")
         ->config([
            'uid' => $uid_rule,
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

 			if(!$this->typeResponseCurl){
            return response()->json([
   				'error' => [
   					'message' => $this->output,
   					'status_code' => 20
   				]
            ]);
         }else{
            $remove2 = $checkpoint2->removeRule2($request);
            sleep(2);

 				$publish = $this->publishChanges($sid);
 				if($publish == "success"){

               //$install = $this->installPolicy();
					return response()->json([
  						'success' => [
 							'message' => "Regla eliminada",
 							'status_code' => 200
  						]
					]);
 				}else return response()->json([
					'error' => [
						'message' => "No se eliminó la regla",
						'status_code' => 20
					]
            ]);
 			}
 		}else{
         return response()->json([
   			'error' => [
   				'message' => "No existe sesión con el checkpoint",
   				'status_code' => 20
   			]
   		]);
      }
  	}

   public function getAllIpsForDelete(Request $request){
 		$object_id = $request['object_id'];
 		$ips = DB::table('fw_address_objects')
 			->join('fw_objects', 'fw_address_objects.object_id', '=', 'fw_objects.id')
 			->where('fw_address_objects.id', $object_id)
 			//->select('ip_initial', 'ip_last')
 			->get();

 		$ip_initial = $ips[0]->ip_initial;
 		$ip_last = $ips[0]->ip_last;

 		$ip_list = [];
 		$i = 0;
 		$networks = Range::parse($ip_initial.'-'.$ip_last);

 		foreach($networks as $network){
			$ip_list[$i]['id'] = $i;
			$ip_list[$i]['text'] = (string)$network;
			$i++;
 		}

 		return response()->json([
 			'success' => [
 				'data' => $ip_list,
 				'status_code' => 200
 			]
 		]);
  	}

   public function getAllIpsByObject(Request $request){
      try{
         $user = JWTAuth::toUser($request['token']);
         //$company_id = $user['company_id'];
         $object_id = $request['object_id'];
         //$role_user = $user->roles->first()->name;

         $ips = DB::table('fw_address_objects AS addr')
            ->join('fw_objects AS obj', 'addr.object_id', '=', 'obj.id')
            ->where('obj.id', '=', $object_id)
            ->select('addr.id AS address_id', 'addr.ip_initial', 'addr.ip_last', 'obj.name', 'obj.id AS object_id')
            ->get();

         $ips = json_decode(json_encode($ips), true);
         $ip_list = [];
         $ip_array = [];

         foreach ($ips as $key => $value) {
            if($value['ip_initial'] == '1.1.1.1'){
               unset($ips[$key]);
            }else{
               $networks = Range::parse($value['ip_initial'].'-'.$value['ip_last']);
               foreach($networks as $network){
                  $ip_array['address'] = (string)$network;
                  $ip_array['address_id'] = $value['address_id'];
                  $ip_array['object_name'] = $value['name'];
                  $ip_array['object_id'] = $value['object_id'];
                  $ip_array['ip_initial'] = $value['ip_initial'];
                  $ip_array['ip_last'] = $value['ip_last'];
                  array_push($ip_list, $ip_array);
               }
            }
         }

         return response()->json([
            'success' => [
              'data' => $ip_list,
              'status_code' => 200
            ]
         ]);
      }catch (Exception $e) {
         //Log::info($e->getMessage());
         return response()->json([
            'error' => [
              'data' => $e->getMessage(),
              'status_code' => 20
            ]
         ]);
      }
   }

   public function getObjectsRules(Request $request){
 		$user = JWTAuth::toUser($request['token']);
 		$company_id = $user['company_id'];
 		$role_user = $user->roles->first()->name;

 		if($role_user == "superadmin"){
 			$obj = FwObject::join('fw_companies', 'fw_objects.company_id', '=', 'fw_companies.id')
 				->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
 				->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
 				->where('fw_objects.server_id', 1)
 				->where('fw_objects.type_object_id', 4)
 				->select('fw_objects.*', 'fw_objects.name AS short', 'fw_companies.name AS company', 'fw_object_types.name AS type', 'fw_servers.name AS server')
 				->get();
 		}else{
 			$obj = FwObject::join('fw_companies', 'fw_objects.company_id', '=', 'fw_companies.id')
 				->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
 				->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
 				->where('company_id', $company_id)
 				->where('fw_objects.server_id', 1)
 				->where('fw_objects.type_object_id', 4)
 				->select('fw_objects.*', 'fw_objects.name AS text', 'fw_companies.name AS company', 'fw_object_types.name AS type', 'fw_servers.name AS server')
 				->get();
 		}

      //ESTO HAY QUE REMOVER PARA MOSTRAR TODOS LOS OBJETOS
 		//AQUI HAY QUE DESCOMPONER LOS NOMBRES Y AGREGARLES 2 POSICIONES A LOS NUEVOS	|| $value['editable'] == 1
      $list_obj = [];
 		$name2 = [];
 		foreach ($obj as  $value) {

         if($role_user == "superadmin"){
            if (strpos($value['name'], 'IP-ADDRESS') !== false ) {
               Log::info("existe");
    				$name = explode('-', $value['name']);
    				$complement_name = $name[2].' '.$name[3];

    				$value['text'] = $value['name'];
    				array_push($list_obj, $value);

    			}elseif ($value['editable'] == 1 || strpos($value['name'], 'IP-ADDRESS') !== true) {
    				$value['text'] = $value['name'];
    				array_push($list_obj, $value);
    			}
         }else{
            if (strpos($value['name'], 'IP-ADDRESS') !== false ) {
               Log::info("no agregar object");
    			}elseif ($value['editable'] == 1 || strpos($value['name'], 'IP-ADDRESS') !== true) {
    				$value['text'] = $value['name'];
    				array_push($list_obj, $value);
    			}
         }
 		}

 		return response()->json([
 			'data' => $list_obj,
 		]);
  	}

   public function getChanges(Request $request){

 		$date_init = \Carbon\Carbon::now()->subDays(1);
 		$date_last = \Carbon\Carbon::now();

 		$date_init = $date_init->toDateString();
 		$date_last = $date_last->toDateString();

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

      Control::curl("172.16.3.114")
      ->is('show-changes')
      ->config([
         'from-date' => $date_init,
         'to-date' => $date_last
      ])
      ->sid($sid)
      ->eCurl(function($response){
         $this->output = $response;
         $this->typeResponseCurl = 1;
      });

 		$resp = json_decode($this->output, true);

 		if(isset($resp['task-id'])){
 			$task = $resp['task-id'];
 			sleep(2);
 			$result_task = $this->showTask($task, $sid);
 			$array_tasks = [];
 			$i = 0;

 			foreach ($result_task['tasks'] as $key => $value) {
 				foreach ($value['task-details'] as $key2 => $value2) {
 					foreach($value2['changes'] as $row){
 						$array_tasks[$i] = array_filter($row['operations']);
 						$i++;
 					}
 				}
 			}

 			$i = 0;
 			$info_changes = [];
 			foreach ($array_tasks as $key => $value) {
 				foreach ($value as $key2 => $value2) {
 					$info_changes[$i]['type_change'] = $key2;
 					$info_changes[$i]['data'] = $value2;

 					$i++;
 				}
 			}

 			$result_changes = $this->evaluateChanges($info_changes);

 			return "success";
 			//return $array_tasks;
 		}elseif(isset($resp['code'])){
 			Log::info($resp['message']);
 			return "unchanged";
 		}
 		else{
 			//Log::info($resp['message']);
 			return "error";
 		}
   }

   public function createDynamicObject(Request $request){
      Log::info("llega al createDynamic");
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){

 			$evaluate = "";
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
				CURLOPT_URL => "https://172.16.3.114/web_api/add-dynamic-object",
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
 				return response()->json([
 					'error' => [
                  'message_admin' => $err,
 						'message' => "Error al conectar con checkpoint. Contacte al administrador.",
 						'status_code' => 20
 					]
 				]);
 			}else{
 				$result = json_decode($response, true);
 				Log::info("RESULT: 114****");
 				Log::info($result);

 				if(isset($result['code'])){
 					Log::info($result['code']);
 					Log::info("entra al if de result code");
 					if($result['code'] == "err_validation_failed"){
 						return response()->json([
 							'error' => [
 								'message' => $result['message'],
 								'status_code' => 20
 							]
 						]);
 					}
 				}else{
               $create2 = $checkpoint2->createDynamicObject2($request);
               sleep(2);

					$uid = $result['uid'];

					$object_type = 4; //Es object dynamic

					$object_new = New FwObject;
					$object_new->name = $new_object_name;
					$object_new->uid = $uid;
					$object_new->type_object_id = $object_type;
					$object_new->server_id = $server_ch;
					$object_new->company_id = $company_id;
					$object_new->tag = $tag;
					$object_new->editable = 1;

					$object_new->save();

					if($object_new->id){
						Log::info("Se creó el objeto checkpoint");

						$bd_obj_check = DB::connection('checkpoint')->table('object_list')->insertGetId(['name' => $new_object_name, 'description' => $comment]);

						if($bd_obj_check){
							Log::info("Se guardó en la bd checkpoint");
						}else{
							Log::info("No se guardó en bd checkpoint");
						}

                  /*$curl = curl_init();

                  curl_setopt_array($curl, array(
                  	CURLOPT_URL => "http://localhost:3500/new_object",
                  	CURLOPT_RETURNTRANSFER => true,
                  	CURLOPT_ENCODING => "",
                  	CURLOPT_MAXREDIRS => 10,
                  	CURLOPT_TIMEOUT => 30,
                  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  	CURLOPT_SSL_VERIFYPEER => false,
                  	CURLOPT_SSL_VERIFYHOST => false,
                  	CURLOPT_CUSTOMREQUEST => "POST",
                  	CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$new_object_name\"\r\n}",
                  	CURLOPT_HTTPHEADER => array(
                  		"content-type: application/json",
                  	),
                  ));

                  $response = curl_exec($curl);
                  $err = curl_error($curl);

                  curl_close($curl);

                  $result = json_decode($response, true);
                  Log::info("RESPUESTA 3500 1");
                  Log::info($result);

                  if($err){
                     Log::info($err);
                     return response()->json([
                        'error' => [
                           'message' => "El objeto no pudo ser creado",
                           'status_code' => 20
                        ]
                     ]);

               	}else{*/
                  $ssh_command = 'tscpgw_api -g "172.16.3.112" -a adddyo -o '.$new_object_name;
                  $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$new_object_name;
                  $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a adddyo -o '.$new_object_name;
                  $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a adddyo -o '.$new_object_name;

                  \SSH::into('checkpoint')->run($ssh_command, function($line){
                  	Log::info($line.PHP_EOL);
                  	$evaluate = $line.PHP_EOL;
                  });

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 112");
                  	\SSH::into('checkpoint')->run($ssh_command, function($line){
                  		Log::info($line.PHP_EOL);
                  		$evaluate = $line.PHP_EOL;
                  	});
                  }

                  sleep(2);

                  \SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  	Log::info($line2.PHP_EOL);
                  	$evaluate = $line2.PHP_EOL;
                  });

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 113");
                  	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  		Log::info($line2.PHP_EOL);
                  		$evaluate = $line2.PHP_EOL;
                  	});
                  }

                  sleep(2);

                  \SSH::into('checkpoint')->run($ssh_command3, function($line3){
                  	Log::info($line3.PHP_EOL);
                  	$evaluate = $line3.PHP_EOL;
                  });

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 116");
                  	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                  		Log::info($line3.PHP_EOL);
                  		$evaluate = $line3.PHP_EOL;
                  	});
                  }

                  sleep(2);

                  \SSH::into('checkpoint')->run($ssh_command4, function($line4){
                  	Log::info($line4.PHP_EOL);
                  	$evaluate = $line4.PHP_EOL;
                  });

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 117");
                  	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                  		Log::info($line4.PHP_EOL);
                  		$evaluate = $line4.PHP_EOL;
                  	});
                  }

                  sleep(2);

                     Log::info("entra al else de node3500");
                     $publish = $this->publishChanges($sid);

   						if($publish == 'success'){

   							$ip_initial = $request['ip_initial'];
   							$ip_last = $request['ip_last'];

   							//Ingreso el rango de ip
   							$object_id = $object_new->id;
   							$type_address_id = 7;//Pertenece a rango de ip para checkpoint

                        /*$curl = curl_init();

                        curl_setopt_array($curl, array(
                        	CURLOPT_URL => "http://localhost:3500/new_object_ips",
                        	CURLOPT_RETURNTRANSFER => true,
                        	CURLOPT_ENCODING => "",
                        	CURLOPT_MAXREDIRS => 10,
                        	CURLOPT_TIMEOUT => 30,
                        	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        	CURLOPT_SSL_VERIFYPEER => false,
                        	CURLOPT_SSL_VERIFYHOST => false,
                        	CURLOPT_CUSTOMREQUEST => "POST",
                        	CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$new_object_name\", \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\", \r\n}",
                        	CURLOPT_HTTPHEADER => array(
                        		"content-type: application/json",
                        	),
                        ));

                        $response = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        $result = json_decode($response, true);
                        Log::info("RESPUESTA 3500 2");
                        Log::info($result);

                        if($err){
                           Log::info($err);
                           return response()->json([
      								'error' => [
      									'message' => "El objeto se creó pero no las Ips",
      									'status_code' => 20
      								]
      							]);
                     	}else{*/

                        $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";
                        $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";
                        $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";
                        $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";


                  		\SSH::into('checkpoint')->run($ssh_command, function($line){
                  			Log::info($line.PHP_EOL);
                  			$evaluate = $line.PHP_EOL;
                  		});

                        $evaluate = $this->output;

                     	while (stripos($evaluate, "try again") !== false) {
                     		Log::info("1 existe try again 112");
                     		\SSH::into('checkpoint')->run($ssh_command, function($line){
                     			Log::info($line.PHP_EOL);
                     			$evaluate = $line.PHP_EOL;
                     		});
                     	}

                  		sleep(2);

                  		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  			Log::info($line2.PHP_EOL);
                  			$evaluate = $line2.PHP_EOL;
                  		});

                        $evaluate = $this->output;

                     	while (stripos($evaluate, "try again") !== false) {
                     		Log::info("1 existe try again 113");
                     		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                     			Log::info($line2.PHP_EOL);
                     			$evaluate = $line2.PHP_EOL;
                     		});
                     	}

                        sleep(2);

                  		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                  			Log::info($line3.PHP_EOL);
                  			$evaluate = $line3.PHP_EOL;
                  		});

                        $evaluate = $this->output;

                     	while (stripos($evaluate, "try again") !== false) {
                     		Log::info("1 existe try again 116");
                     		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                     			Log::info($line3.PHP_EOL);
                     			$evaluate = $line3.PHP_EOL;
                     		});
                     	}

                        sleep(2);

                  		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                  			Log::info($line4.PHP_EOL);
                  			$evaluate = $line4.PHP_EOL;
                  		});

                        $evaluate = $this->output;

                     	while (stripos($evaluate, "try again") !== false) {
                     		Log::info("1 existe try again 117");
                     		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                     			Log::info($line4.PHP_EOL);
                     			$evaluate = $line4.PHP_EOL;
                     		});
                     	}

                        sleep(2);

                        Log::info("ip agregada ch");
   							$addr_obj = new AddressObject;
   							$addr_obj->ip_initial = $ip_initial;
   							$addr_obj->ip_last = $ip_last;
   							$addr_obj->object_id = $object_id;
   							$addr_obj->type_address_id = $type_address_id;
   							$addr_obj->save();

   							if($addr_obj){
                           $bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $bd_obj_check, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(),
   								'updated_at' => \Carbon\Carbon::now()]);

   								if($bd_ips_check){
                              $now = \Carbon\Carbon::now();
                              Session::put('time_execution', $now);

   									return response()->json([
   										'success' => [
   											'message' => "Objeto creado exitosamente",
   											'status_code' => 200
   										]
   									]);
   								}else{
   									return response()->json([
   										'success' => [
   											'message' => "Se creó el objeto pero no las ips",
   											'status_code' => 200
   										]
   									]);
   								}
   							}else{
   								return response()->json([
   									'success' => [
   										'message' => "Se creó el objeto pero no las ips",
   										'status_code' => 200
   									]
   								]);
   							}
                        //}
   						}else{
                        Log::info("Error al publicar!!");
   							return response()->json([
   								'error' => [
   									'message' => "El objeto no pudo ser creado",
   									'status_code' => 20
   								]
   							]);
   						}
                  //}
					}else{
                  Log::info("Error al guardar obj en la bdd!!");
						return response()->json([
							'error' => [
								'message' => "El objeto no pudo ser creado",
								'status_code' => 20
							]
						]);
					}
				}
 			}
 		}else{
 			return response()->json([
 				'error' => [
 					'message' => "Error al crear el objeto",
 					'status_code' => 20
 				]
 			]);
 		}
  	}

   public function getDynamicObjects(Request $request){
 		$user = JWTAuth::toUser($request['token']);
 		$company_id = $user['company_id'];
 		$role_user = $user->roles->first()->name;

 		if($role_user == "superadmin"){
         //Log::info("super");
 			$obj = FwObject::join('fw_companies', 'fw_objects.company_id', '=', 'fw_companies.id')
 				->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
 				->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
 				->where('fw_objects.server_id', 1)
 				->where('fw_objects.type_object_id', 4)
 				->select('fw_objects.*', 'fw_objects.name AS short_name', 'fw_companies.name AS company', 'fw_object_types.name AS type', 'fw_servers.name AS server')
 				->get();
 		}else{
         //Log::info("else");
 			$obj = FwObject::join('fw_companies', 'fw_objects.company_id', '=', 'fw_companies.id')
 				->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
 				->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
 				->where('company_id', $company_id)
 				->where('fw_objects.server_id', 1)
 				->where('fw_objects.type_object_id', 4)
 				->select('fw_objects.*', 'fw_objects.name AS short_name', 'fw_companies.name AS company', 'fw_object_types.name AS type', 'fw_servers.name AS server')
 				->get();
 		}

      //Log::info($obj);

 		//ESTO HAY QUE REMOVER PARA MOSTRAR TODOS LOS OBJETOS
 		//AQUI HAY QUE DESCOMPONER LOS NOMBRES Y AGREGARLES 2 POSICIONES A LOS NUEVOS	|| $value['editable'] == 1
      $list_obj = [];
 		$name2 = [];
 		foreach ($obj as  $value) {

         if($role_user == "superadmin"){
            /*if (strpos($value['name'], 'IP-ADDRESS') !== false ) {
               Log::info("existe");
    				$name = explode('-', $value['name']);
    				$complement_name = $name[2].' '.$name[3];

    				$value['short_name'] = $complement_name;
    				array_push($list_obj, $value);

    			}elseif ($value['editable'] == 1 || strpos($value['name'], 'IP-ADDRESS') !== true) {

    				$value['short_name'] = $value['name'];
    				array_push($list_obj, $value);
    			}*/
            if($value['editable'] == 1){
               $value['short_name'] = $value['name'];
    				array_push($list_obj, $value);
            }else{
               $name = explode('-', $value['name']);
    				$complement_name = $name[2].' '.$name[3];

    				$value['short_name'] = $complement_name;
    				array_push($list_obj, $value);
            }
         }else{
            if (strpos($value['name'], 'IP-ADDRESS') !== false ) {
               //Log::info("no agregar object");
    			}elseif($value['editable'] == 1){
               $value['short_name'] = $value['name'];
    				array_push($list_obj, $value);
            }else{
               $name = explode('-', $value['name']);
    				$complement_name = $name[2].' '.$name[3];

    				$value['short_name'] = $complement_name;
    				array_push($list_obj, $value);
            }

         }
 		}

 		$new_obj = json_decode(json_encode($list_obj), true);

 		return response()->json([
 			'data' => $list_obj
 		]);
  	}

   public function addObjectCompany($data){
		Log::info($data);
		Log::info("*************************************");

      $checkpoint2 = new CheckPointFunctionController;

		if(Session::has('sid_session'))
			$sid = Session::get('sid_session');
  		else $sid = $this->getLastSession();

  		if($sid){

         $server_ch = 1; //Es el id del checkpoint
  			$new_object_name = $data['object_name'];
  			$tag = $data['tag'];
  			$comment = "Prueba code";

  			$ip_initial = '1.1.1.1';
  			$ip_last = '1.1.1.1';

  			$company_id = $data['company_id'];

         $curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/add-dynamic-object",
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
            Log::info("Resutlado obj 114");
  				Log::info($result);

  				if(isset($result['code'])){
  					Log::info($result['code']);
  					return response()->json([
  						'error' => [
  							'message' => $result['message'],
  							'status_code' => 20,
  							'error' => "Existe error en checkpoint"
  						]
  					]);
  					//if($result['code'] == "generic_err_object_not_found"){}
  				}else{

  					$uid = $result['uid'];
  					$object_type = 4; //Es object dynamic

  					$object_new = New FwObject;
  					$object_new->name = $new_object_name;
  					$object_new->uid = $uid;
  					$object_new->type_object_id = $object_type;
  					$object_new->server_id = $server_ch;
  					$object_new->company_id = $company_id;
  					$object_new->tag = $tag;
  					$object_new->save();

  					if($object_new->id){
  						#Log::info("Se creó el objeto checkpoint");
  						$bd_obj_check = DB::connection('checkpoint')->table('object_list')->insertGetId(['name' => $new_object_name, 'description' => $comment]);

  						if($bd_obj_check){
  							#Log::info("Se guardó en la bd checkpoint");
  						}else{
  							#Log::info("No se guardó en bd checkpoint");
  						}

                  $curl = curl_init();

                  curl_setopt_array($curl, array(
                     CURLOPT_URL => "http://localhost:3500/new_object",
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_ENCODING => "",
                     CURLOPT_MAXREDIRS => 10,
                     CURLOPT_TIMEOUT => 30,
                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                     CURLOPT_SSL_VERIFYPEER => false,
                     CURLOPT_SSL_VERIFYHOST => false,
                     CURLOPT_CUSTOMREQUEST => "POST",
                     CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$new_object_name\"\r\n}",
                     CURLOPT_HTTPHEADER => array(
                        "content-type: application/json",
                     ),
                  ));

                  $response = curl_exec($curl);
                  $err = curl_error($curl);

                  curl_close($curl);

                  if($err){

                    return response()->json([
                       'error' => [
                          'message' => "El objeto no pudo ser creado",
                          'status_code' => 20
                       ]
                    ]);

                  }else{
                     $publish = $this->publishChanges($sid);
     						if($publish == 'success'){
    							#Log::info("publish success");
    							$object_id = $object_new->id;
    							$type_address_id = 7;//Pertenece a rango de ip para checkpoint
    							#$ip_address = $ip_initial.'-'.$ip_last;

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        	CURLOPT_URL => "http://localhost:3500/new_object_ips",
                        	CURLOPT_RETURNTRANSFER => true,
                        	CURLOPT_ENCODING => "",
                        	CURLOPT_MAXREDIRS => 10,
                        	CURLOPT_TIMEOUT => 30,
                        	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        	CURLOPT_SSL_VERIFYPEER => false,
                        	CURLOPT_SSL_VERIFYHOST => false,
                        	CURLOPT_CUSTOMREQUEST => "POST",
                        	CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$new_object_name\", \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\", \r\n}",
                        	CURLOPT_HTTPHEADER => array(
                        		"content-type: application/json",
                        	),
                        ));

                        $response = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        if($err){
                           return response()->json([
      								'error' => [
      									'message' => "El objeto se creó pero no las Ips",
      									'status_code' => 20
      								]
      							]);
                     	}else{
                           #Log::info("ip agregada ch");
       							$addr_obj = new AddressObject;
       							$addr_obj->ip_initial = $ip_initial;
       							$addr_obj->ip_last = $ip_last;
       							$addr_obj->object_id = $object_id;
       							$addr_obj->type_address_id = $type_address_id;
       							$addr_obj->save();

       							if($addr_obj){
       								$bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $bd_obj_check, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(),
       								'updated_at' => \Carbon\Carbon::now()]);

       								if($bd_ips_check) return "success";
       								else return "error";
       							}else return "error";
                        }
     						}else return "error";
                  }
  					}else return "error";
  				}
  			}
  		}else return "error";
  	}

   public function removeObject(Request $request){

      $checkpoint2 = new CheckPointFunctionController;
      $evaluate = "";
 		$object_id = $request['object_id'];
 		$object_name = $request['object_name'];

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

      if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
			  	CURLOPT_URL => "https://172.16.3.114/web_api/delete-dynamic-object",
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
				return response()->json([
					'error' => [
						'message' => $err,
						'status_code' => 20
					]
				]);
			}else{

 				$result = json_decode($this->output, true);
            Log::info($result);

 				if(isset($result['code'])){
 					return response()->json([
 						'error' => [
 							'message' => $result['message'],
 							'status_code' => 20
 						]
 					]);
 				}else{
 					$publish = $this->publishChanges($sid);

 					if($publish == "success"){

                  $remove2 = $checkpoint2->removeObject2($request);
                  sleep(2);

                  /*$curl = curl_init();

         			curl_setopt_array($curl, array(
         			  	CURLOPT_URL => "http://localhost:3500/del_object",
         			  	CURLOPT_RETURNTRANSFER => true,
         			  	CURLOPT_ENCODING => "",
         			  	CURLOPT_MAXREDIRS => 10,
         			  	CURLOPT_TIMEOUT => 30,
         			  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         			  	CURLOPT_CUSTOMREQUEST => "POST",
         				CURLOPT_SSL_VERIFYPEER => false,
         				CURLOPT_SSL_VERIFYHOST => false,
         				CURLOPT_POSTFIELDS => "{\r\n \"object_name\" : \"$object_name\"\r\n}",
         			  	CURLOPT_HTTPHEADER => array(
         			    	"content-type: application/json"
         			  	),
         			));

         			$response = curl_exec($curl);
         			$err = curl_error($curl);

         			curl_close($curl);

         			if($err){
         				return response()->json([
         					'error' => [
         						'message' => $err,
         						'status_code' => 20
         					]
         				]);
         			}else{*/
                  $ssh_command = 'tscpgw_api -g "172.16.3.112" -a deldyo -o '.$object_name;
                  $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$object_name;
                  $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a adddyo -o '.$object_name;
                  $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a adddyo -o '.$object_name;

                  \SSH::into('checkpoint')->run($ssh_command, function($line){
                  	Log::info($line.PHP_EOL);
                  	$evaluate = $line.PHP_EOL;
                  });

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 112");
                  	\SSH::into('checkpoint')->run($ssh_command, function($line){
                  		Log::info($line.PHP_EOL);
                  		$evaluate = $line.PHP_EOL;
                  	});
                  }

               	sleep(2);

               	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
               		Log::info($line2.PHP_EOL);
               		$evaluate = $line2.PHP_EOL;
               	});

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 113");
                  	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  		Log::info($line2.PHP_EOL);
                  		$evaluate = $line2.PHP_EOL;
                  	});
                  }

                  sleep(2);

               	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
               		Log::info($line3.PHP_EOL);
               		$evaluate = $line3.PHP_EOL;
               	});

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 116");
                  	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                  		Log::info($line3.PHP_EOL);
                  		$evaluate = $line3.PHP_EOL;
                  	});
                  }

                  sleep(2);

               	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
               		Log::info($line4.PHP_EOL);
               		$evaluate = $line4.PHP_EOL;
               	});

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 117");
                  	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                  		Log::info($line4.PHP_EOL);
                  		$evaluate = $line4.PHP_EOL;
                  	});
                  }

                  sleep(2);

                  $delete = DB::table('fw_objects')->where('id', '=', $object_id)->delete();

 						if($delete){

 							$delete_adds = DB::table('fw_address_objects')->where('object_id', '=', $object_id)->delete();

 							$obj_checkpoint_db = DB::connection('checkpoint')->select('SELECT * FROM object_list WHERE name="'.$object_name.'" ');
 							$object_id_bd = json_decode(json_encode($obj_checkpoint_db), true);

 							foreach($object_id_bd as $row){
 								$id_obj_list = $row['id'];
 							}

 							if($delete_adds){

 								$delete_obj_db = DB::connection('checkpoint')->delete("DELETE FROM object_list WHERE id=".$id_obj_list);
 								$delete_add_db = DB::connection('checkpoint')->delete("DELETE FROM ip_object_list WHERE object_id=".$id_obj_list);

 								return response()->json([
 									'success' => [
 										'message' => 'Objeto eliminado',
 										'status_code' => 200
 									]
 								]);
 							}else{
 								return response()->json([
 									'success' => [
 										'message' => 'Objeto eliminado pero las ips asignadas no fueron eliminadas.',
 										'status_code' => 200
 									]
 								]);
 							}
 						}else{
 							return response()->json([
 								'error' => [
 									'message' => 'error al eliminar el objeto de la bdd',
 									'status_code' => 20
 								]
 							]);
 						}
                  //}
 					}else{
 						return response()->json([
 							'error' => [
 								'message' => "No se eliminó el objeto",
 								'status_code' => 20
 							]
 						]);
 					}
 				}
 			}
 		}else{
 			return response()->json([
 				'error' => [
 					'message' => 'No existe sid',
 					'status_code' => 20
 				]
 			]);
 		}
   }

   public function removeIpObject(Request $request){
 		Log::info($request);
      $evaluate = "";

 		$object_id = $request['object_id'];
 		$address_id = $request['address_id'];
 		$ip_initial = $request['ip_init'];
 		$ip_last = $request['ip_last'];
 		$type_remove = $request['type_remove'];
 		$type_address_id = 7;//Pertenece a rango de ip para checkpoint

 		$evaluate;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){

 			$object = DB::table('fw_objects')->where('id', $object_id)->get();
 			$object = json_decode(json_encode($object), true);
 			$object_name = $object[0]['name'];

 			$address = DB::table('fw_address_objects')->where('id', $address_id)->get();
 			$address = json_decode(json_encode($address), true);

 			if($type_remove == 1){//Elimina el rango completo

 				if($ip_initial == $ip_last){

 					Log::info("rango igual");

               /*$curl = curl_init();

               curl_setopt_array($curl, array(
                  CURLOPT_URL => "http://localhost:3500/del_object_ips",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_SSL_VERIFYHOST => false,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\", \r\n}",
                  CURLOPT_HTTPHEADER => array(
                  	"content-type: application/json",
                  ),
               ));

               $response = curl_exec($curl);
               $err = curl_error($curl);

               curl_close($curl);

               if($err){
                  Log::info("No se creó el primer rango");
               }else{*/
               $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
               $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
               $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
               $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";


         		\SSH::into('checkpoint')->run($ssh_command, function($line){
         			Log::info($line.PHP_EOL);
         			$evaluate = $line.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 112");
            		\SSH::into('checkpoint')->run($ssh_command, function($line){
            			Log::info($line.PHP_EOL);
            			$evaluate = $line.PHP_EOL;
            		});
            	}

         		sleep(2);

         		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
         			Log::info($line2.PHP_EOL);
         			$evaluate = $line2.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 113");
            		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
            			Log::info($line2.PHP_EOL);
            			$evaluate = $line2.PHP_EOL;
            		});
            	}

               sleep(2);

         		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
         			Log::info($line3.PHP_EOL);
         			$evaluate = $line3.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 116");
            		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
            			Log::info($line3.PHP_EOL);
            			$evaluate = $line3.PHP_EOL;
            		});
            	}

               sleep(2);

         		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
         			Log::info($line4.PHP_EOL);
         			$evaluate = $line4.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 117");
            		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
            			Log::info($line4.PHP_EOL);
            			$evaluate = $line4.PHP_EOL;
            		});
            	}

               sleep(2);

               $publish = $this->publishChanges($sid);

 					if($publish == "success"){
 						$delete_add = DB::table('fw_address_objects')->where('id', '=', $address_id)->delete();

 						//$delete_add_ch = DB::connection('checkpoint')->delete("DELETE ip_object_list SET ip_initial='".$request['new_ip_initial']."', ip_last='".$request['new_ip_last']."' WHERE object_id=".$object_id);
 						if($delete_add){
 							return response()->json([
 								'success' => [
 									'data' => 'Rango de ips eliminado',
 									'status_code' => 200
 								]
 							]);
 						}else{
 							return response()->json([
 								'error' => [
 									'message' => 'error al eliminar el rango de ips',
 									'status_code' => 20
 								]
 							]);
 						}
 					}else{
 						return response()->json([
 							'error' => [
 								'message' => 'error al eliminar el rango de ips',
 								'status_code' => 20
 							]
 						]);
 					}
               //}
 				}else{//Si entra aquí es porque se eliminará un rango

 					Log::info("diferentes ip");

 					$ip_initial_range = $address[0]['ip_initial'];
 					$ip_last_range = $address[0]['ip_last'];

 					$ip1_delete = explode(".", $ip_initial);
 					$ip2_delete = explode(".", $ip_last);

 					$second_add_oct = $ip1_delete[3] - 1;
 					$third_add_oct = $ip2_delete[3] + 1;

 					Log::info($second_add_oct);
 					Log::info($third_add_oct);

 					//Con los octetos creo las ips necesarias para formar los 2 rangos
 					$second_new_ip = $ip1_delete[0].'.'.$ip1_delete[1].'.'.$ip1_delete[2].'.'.$second_add_oct;
 					$third_new_ip = $ip2_delete[0].'.'.$ip2_delete[1].'.'.$ip2_delete[2].'.'.$third_add_oct;

 					$new_range_one = $ip_initial_range.' '.$second_new_ip;
 					$new_range_two = $third_new_ip.' '.$ip_last_range;

 					//Ejecuto el comando para eliminar el rango actual

               /*$curl = curl_init();

               curl_setopt_array($curl, array(
                  CURLOPT_URL => "http://localhost:3500/del_object_ips",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_SSL_VERIFYHOST => false,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\", \r\n}",
                  CURLOPT_HTTPHEADER => array(
                  	"content-type: application/json",
                  ),
               ));

               $response = curl_exec($curl);
               $err = curl_error($curl);

               curl_close($curl);

               if($err){
                  Log::info("No se creó el primer rango");
               }else{*/
               $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
               $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
               $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
               $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

         		\SSH::into('checkpoint')->run($ssh_command, function($line){
         			Log::info($line.PHP_EOL);
         			$evaluate = $line.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 112");
            		\SSH::into('checkpoint')->run($ssh_command, function($line){
            			Log::info($line.PHP_EOL);
            			$evaluate = $line.PHP_EOL;
            		});
            	}

         		sleep(2);

         		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
         			Log::info($line2.PHP_EOL);
         			$evaluate = $line2.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 113");
            		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
            			Log::info($line2.PHP_EOL);
            			$evaluate = $line2.PHP_EOL;
            		});
            	}

               sleep(2);

         		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
         			Log::info($line3.PHP_EOL);
         			$evaluate = $line3.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 116");
            		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
            			Log::info($line3.PHP_EOL);
            			$evaluate = $line3.PHP_EOL;
            		});
            	}

               sleep(2);

         		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
         			Log::info($line4.PHP_EOL);
         			$evaluate = $line4.PHP_EOL;
         		});

               $evaluate = $this->output;

            	while (stripos($evaluate, "try again") !== false) {
            		Log::info("1 existe try again 117");
            		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
            			Log::info($line4.PHP_EOL);
            			$evaluate = $line4.PHP_EOL;
            		});
            	}

               sleep(2);

               //publico los nuevos cambios
 					$publish = $this->publishChanges($sid);

 					if(Range::parse($ip_initial.'-'.$ip_last)->contains(new IP($second_new_ip)) && Range::parse($ip_initial.'-'.$ip_last)->contains(new IP($third_new_ip))){

 						if($publish == "success"){

 							//Elimino el rango de la bdd
 							$delete_add = DB::table('fw_address_objects')->where('id', '=', $address_id)->delete();

 							if($delete_add){
 								/**************/
                        /*$curl = curl_init();

                        curl_setopt_array($curl, array(
                           CURLOPT_URL => "http://localhost:3500/new_object_ips",
                           CURLOPT_RETURNTRANSFER => true,
                           CURLOPT_ENCODING => "",
                           CURLOPT_MAXREDIRS => 10,
                           CURLOPT_TIMEOUT => 30,
                           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                           CURLOPT_SSL_VERIFYPEER => false,
                           CURLOPT_SSL_VERIFYHOST => false,
                           CURLOPT_CUSTOMREQUEST => "POST",
                           CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$ip_initial_range\", \"ip_last\" : \"$second_new_ip\", \r\n}",
                           CURLOPT_HTTPHEADER => array(
                           	"content-type: application/json",
                           ),
                        ));

                        $response = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        if($err){
                           Log::info("No se creó el primer rango");
                        }else{*/

                           $curl = curl_init();

                           curl_setopt_array($curl, array(
                              CURLOPT_URL => "http://localhost:3500/new_object_ips",
                              CURLOPT_RETURNTRANSFER => true,
                              CURLOPT_ENCODING => "",
                              CURLOPT_MAXREDIRS => 10,
                              CURLOPT_TIMEOUT => 30,
                              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                              CURLOPT_SSL_VERIFYPEER => false,
                              CURLOPT_SSL_VERIFYHOST => false,
                              CURLOPT_CUSTOMREQUEST => "POST",
                              CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$third_new_ip\", \"ip_last\" : \"$ip_last_range\", \r\n}",
                              CURLOPT_HTTPHEADER => array(
                              	"content-type: application/json",
                              ),
                           ));

                           $response = curl_exec($curl);
                           $err = curl_error($curl);

                           curl_close($curl);

                           if($err){
                              Log::info("No se creó el segundo rango");
                           }else{

                              $publish2 = $this->publishChanges($sid);

       								if($publish2 == "success"){
       									//Creo un array con los datos de los nuevos rangos
       									$arr_addr = array(
       										0 => array(
       											'ip_initial' => $ip_initial_range,
       											'ip_last' => $second_new_ip,
       											'object_id' => $object_id,
       											'type_address_id' => $type_address_id,
       											'created_at' => date('Y-m-d H:i:s'),
       											'updated_at' => date('Y-m-d H:i:s'),
       										),
       										1 => array(
       											'ip_initial' => $third_new_ip,
       											'ip_last' => $ip_last_range,
       											'object_id' => $object_id,
       											'type_address_id' => $type_address_id,
       											'created_at' => date('Y-m-d H:i:s'),
       											'updated_at' => date('Y-m-d H:i:s'),
       										),
       									);

       									//inserto en la base los nuevos rangos
       									$insert = DB::table('fw_address_objects')->insert($arr_addr);

       									if($insert){
       										return response()->json([
       											'success' => [
       												'data' => "Rango eliminado correctamente",
       												'status_code' => 200
       											]
       										]);
       									}else{
       										return response()->json([
       											'error' => [
       												'message' => 'Rangos publicado en checkpoint pero no se guardó en la bdd',
       												'status_code' => 20
       											]
       										]);
       									}
       								}else{
       									return response()->json([
       										'error' => [
       											'message' => 'No se pudieron guardar los nuevos rangos',
       											'status_code' => 20
       										]
       									]);
       								}
                           }
                        //}

 							}else{
 								return response()->json([
 									'error' => [
 										'message' => 'No se pudieron guardar los nuevos rangos',
 										'status_code' => 20
 									]
 								]);
 							}
 						}else{
 							return response()->json([
 								'error' => [
 									'message' => 'No se pudo guardar el nuevo rango',
 									'status_code' => 20
 								]
 							]);
 						}
 					}else{

 						if($publish == "success"){

 							//Elimino el rango de la bdd
 							$delete_add = DB::table('fw_address_objects')->where('id', '=', $address_id)->delete();

 							if($delete_add){
 								return response()->json([
 									'success' => [
 										'data' => "Rango eliminado correctamente",
 										'status_code' => 200
 									]
 								]);
 							}else{
 								return response()->json([
 									'error' => [
 										'message' => 'No se pudo eliminar el rango',
 										'status_code' => 20
 									]
 								]);
 							}
 						}else{
 							return response()->json([
 								'error' => [
 									'message' => 'No se pudo eliminar el rango',
 									'status_code' => 20
 								]
 							]);
 						}
 					}

               //}
 				}
 			}elseif($type_remove == 2) {//Elimina 1 ip del rango

 				Log::info("entra al elseif");

 				$add_initial = $address[0]['ip_initial'];
 				$add_last = $address[0]['ip_last'];

 				$address_rem = $request['ip_init'];

 				if($add_initial == $add_last){
 					Log::info("es una sola IP");

               $curl = curl_init();

               curl_setopt_array($curl, array(
                  CURLOPT_URL => "http://localhost:3500/del_object_ips",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_SSL_VERIFYHOST => false,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$add_initial\", \"ip_last\" : \"$add_last\", \r\n}",
                  CURLOPT_HTTPHEADER => array(
                  	"content-type: application/json",
                  ),
               ));

               $response = curl_exec($curl);
               $err = curl_error($curl);

               curl_close($curl);

               if($err){
                  Log::info("No se creó el primer rango");
               }else{
                  $publish = $this->publishChanges($sid);

    					if($publish == "success"){
    						//Elimino el rango de la bdd
    						$delete_add = DB::table('fw_address_objects')->where('id', '=', $address_id)->delete();

    						if($delete_add){
    							return response()->json([
    								'success' => [
    									'message' => 'Se eliminó correctamente',
    									'status_code' => 200
    								]
    							]);
    						}else{
    							return response()->json([
    								'error' => [
    									'message' => 'Se eliminó del checkpoint pero no en la bdd',
    									'status_code' => 20
    								]
    							]);
    						}
    					}else{
    						return response()->json([
    							'error' => [
    								'message' => 'No se pudo publicar el cambio',
    								'status_code' => 20
    							]
    						]);
    					}
               }
 				}else{
 					Log::info("Es una IP entre un rango");

 					//Verifico que la ip a eliminar exista entre el rango
 					if(Range::parse($add_initial.'-'.$add_last)->contains(new IP($address_rem))){
 						//separo en octetos la ip
 						$add_oct = explode(".", $address_rem);

 						$second_add_oct = $add_oct[3] - 1;
 						$third_add_oct = $add_oct[3] + 1;

 						//Con los octetos creo las ips necesarias para formar los 2 rangos
 						$second_ip = $add_oct[0].'.'.$add_oct[1].'.'.$add_oct[2].'.'.$second_add_oct;
 						$third_ip = $add_oct[0].'.'.$add_oct[1].'.'.$add_oct[2].'.'.$third_add_oct;

 						$range_one = $add_initial.' '.$second_ip;
 						$range_two = $third_ip.' '.$add_last;

                  $curl = curl_init();

                  curl_setopt_array($curl, array(
                     CURLOPT_URL => "http://localhost:3500/del_object_ips",
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_ENCODING => "",
                     CURLOPT_MAXREDIRS => 10,
                     CURLOPT_TIMEOUT => 30,
                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                     CURLOPT_SSL_VERIFYPEER => false,
                     CURLOPT_SSL_VERIFYHOST => false,
                     CURLOPT_CUSTOMREQUEST => "POST",
                     CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$add_initial\", \"ip_last\" : \"$add_last\", \r\n}",
                     CURLOPT_HTTPHEADER => array(
                     	"content-type: application/json",
                     ),
                  ));

                  $response = curl_exec($curl);
                  $err = curl_error($curl);

                  curl_close($curl);

                  if($err){
                     Log::info("No se creó el primer rango");
                  }else{

                     //publico los nuevos cambios
    						$publish = $this->publishChanges($sid);

    						if($publish == "success"){
    							//Elimino el rango de la bdd
    							$delete_add = DB::table('fw_address_objects')->where('id', '=', $address_id)->delete();

    							if($delete_add){

                           $curl = curl_init();

                           curl_setopt_array($curl, array(
                              CURLOPT_URL => "http://localhost:3500/new_object_ips",
                              CURLOPT_RETURNTRANSFER => true,
                              CURLOPT_ENCODING => "",
                              CURLOPT_MAXREDIRS => 10,
                              CURLOPT_TIMEOUT => 30,
                              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                              CURLOPT_SSL_VERIFYPEER => false,
                              CURLOPT_SSL_VERIFYHOST => false,
                              CURLOPT_CUSTOMREQUEST => "POST",
                              CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$add_initial\", \"ip_last\" : \"$add_last\", \r\n}",
                              CURLOPT_HTTPHEADER => array(
                              	"content-type: application/json",
                              ),
                           ));

                           $response = curl_exec($curl);
                           $err = curl_error($curl);

                           curl_close($curl);

                           if($err){
                              Log::info("No se creó el primer rango");
                           }else{

                              $curl = curl_init();

                              curl_setopt_array($curl, array(
                                 CURLOPT_URL => "http://localhost:3500/new_object_ips",
                                 CURLOPT_RETURNTRANSFER => true,
                                 CURLOPT_ENCODING => "",
                                 CURLOPT_MAXREDIRS => 10,
                                 CURLOPT_TIMEOUT => 30,
                                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                 CURLOPT_SSL_VERIFYPEER => false,
                                 CURLOPT_SSL_VERIFYHOST => false,
                                 CURLOPT_CUSTOMREQUEST => "POST",
                                 CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$third_ip\", \"ip_last\" : \"$add_last\", \r\n}",
                                 CURLOPT_HTTPHEADER => array(
                                 	"content-type: application/json",
                                 ),
                              ));

                              $response = curl_exec($curl);
                              $err = curl_error($curl);

                              curl_close($curl);

                              if($err){
                                 Log::info("No se creó el segundo rango");
                              }else{

                                 $publish2 = $this->publishChanges($sid);

          								if($publish2 == "success"){
          									//Creo un array con los datos de los nuevos rangos
          									$arr_addr = array(
          										0 => array(
          											'ip_initial' => $add_initial,
          											'ip_last' => $second_ip,
          											'object_id' => $object_id,
          											'type_address_id' => $type_address_id,
          											'created_at' => date('Y-m-d H:i:s'),
          											'updated_at' => date('Y-m-d H:i:s'),
          										),
          										1 => array(
          											'ip_initial' => $third_ip,
          											'ip_last' => $add_last,
          											'object_id' => $object_id,
          											'type_address_id' => $type_address_id,
          											'created_at' => date('Y-m-d H:i:s'),
          											'updated_at' => date('Y-m-d H:i:s'),
          										),
          									);

          									//inserto en la base los nuevos rangos
          									$insert = DB::table('fw_address_objects')->insert($arr_addr);

          									if($insert){
          										return response()->json([
          											'success' => [
          												'data' => "IP eliminada correctamente",
          												'status_code' => 200
          											]
          										]);
          									}else{
          										return response()->json([
          											'error' => [
          												'message' => 'Rango publicado en checkpoint pero no se guardó en la bdd',
          												'status_code' => 20
          											]
          										]);
          									}
          								}else{
          									return response()->json([
          										'error' => [
          											'message' => 'No se pudo guardar el nuevo rango',
          											'status_code' => 20
          										]
          									]);
          								}

                              }
    								}
    							}else{
    								return response()->json([
    									'error' => [
    										'message' => 'No se pudo guardar el nuevo rango',
    										'status_code' => 20
    									]
    								]);
    							}
    						}else{
    							return response()->json([
    								'error' => [
    									'message' => 'No se pudo guardar el nuevo rango',
    									'status_code' => 20
    								]
    							]);
    						}
                  }

 					}else{
 						return response()->json([
 							'error' => [
 								'message' => 'La ip a borrar no existe en el rango',
 								'status_code' => 20
 							]
 						]);
 					}
 				}

 			}elseif($type_remove == 3) {//Elimina varias ips del rango
 				# code...
 			}
 		}else{
 			return response()->json([
 				'error' => [
 					'message' => 'No existe sesión',
 					'status_code' => 20
 				]
 			]);
 		}
   }

   public function createTag($tag){
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

      if($sid){

         $curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/add-tag",
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

   public function getUniqueRule(){

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){
         Control::curl("172.16.3.114")
         ->is("show-access-rulebase")
         ->config([
            'offset' => 0,
            'name' => 'Network',
            'limit' => 100,
            'details-level' => 'full',
            'use-object-dictionary' => true
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
 				return response()->json([
 					'error' => [
 						'message' => $this->output,
 						'status_code' => 20
 					]
 				]);
 			}else{
 				$result = json_decode($this->output, true);
 				$data = [];
 				$i = 0;

 				foreach($result['rulebase'] as $key => $value) {
 					foreach($value['rulebase'] as $row){
 						$data[$i]['section'] = $value['name'];
 						$data[$i]['name'] = $row['name'];
 						$data[$i]['type'] = $row['type'];
 						$data[$i]['rule_number'] = array($row['rule-number']);
 						$data[$i]['source'] = $row['source'];
 						$data[$i]['destination'] = $row['destination'];
 						$data[$i]['service'] = $row['service'];
 						$data[$i]['vpn'] = $row['vpn'];
 						$data[$i]['action'] = $row['action'];
 						$data[$i]['track'] = $row['track'];

 						$i++;
 					}
 				}

 				return response()->json([
 					'data' => $data,
 					'status_code' => 200
 				]);
 			}

 		}else{
 			return "error";
 		}
   }

   public function moveRule(Request $request){
      $checkpoint2 = new CheckPointFunctionController;

   	if(Session::has('sid_session'))
   		$sid = Session::get('sid_session');
   	else $sid = $this->getLastSession();

  		if($sid){

  			$uid_rule = $request['uid'];
  			$name_rule = $request['name'];
  			$position = $request['position'];
  			$rule_change = $request['name_change'];

         Control::curl("172.16.3.114")
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
         Log::info("INFO move RUle 114");
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
  						return response()->json([
  							'error' => [
  								'message' => $result['message'],
  								'status_code' => 20
  							]
  						]);
  					}
  				}else{

  					$publish = $this->publishChanges($sid);

  					if($publish == "success"){

                  $moverule2 = $checkpoint2->moveRule2($request);
                  sleep(2);

  						return response()->json([
  							'success' => [
  								'message' => "Regla Movida",
  								'status_code' => 200
  							]
  						]);
  					}else{
  						return response()->json([
  							'error' => [
  								'message' => "No se movió la regla",
  								'status_code' => 20
  							]
  						]);
  					}
  				}
  			}
  		}else{
  			return response()->json([
  				'error' => [
  					'message' => "No existe sesión con el checkpoint",
  					'status_code' => 20
  				]
  			]);
  		}
  	}

   public function editIpsObject(Request $request){
      $evaluate = "";
 		$addr_id = $request['object_info']['id'];
 		$object_id = $request['object_info']['object_id'];
 		$object_name = $request['object_info']['objeto'];
 		$new_range = $request['new_ip_initial'].' '.$request['new_ip_last'];
      $new_ip_initial = $request['new_ip_initial'];
      $new_ip_last = $request['new_ip_last'];
 		$old_range = $request['object_info']['ip_initial'].' '.$request['object_info']['ip_last'];
 		$ip1 = $request['new_ip_initial'];
 		$ip2 = $request['new_ip_last'];

 		$ip_object = AddressObject::find($addr_id);
 		$ip_object->ip_initial = $ip1;
 		$ip_object->ip_last = $ip2;
 		$ip_object->save();

 		if($ip_object){

 			Log::info($old_range);
 			Log::info($new_range);

         /*******CODIGO PARA BORRAR IPS******/
         /*Control::ssh(['172.16.3.*',['112','113']])
         ->raw("-a delrip {$object_name} -r {$old_range}")
         ->eSSH(function($response){}, false);
 			sleep(3);*/

         $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$request['object_info']['ip_initial']." ".$request['object_info']['ip_last']."'";
         $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$request['object_info']['ip_initial']." ".$request['object_info']['ip_last']."'";
         $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$request['object_info']['ip_initial']." ".$request['object_info']['ip_last']."'";
         $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$request['object_info']['ip_initial']." ".$request['object_info']['ip_last']."'";


         \SSH::into('checkpoint')->run($ssh_command, function($line){
         	Log::info($line.PHP_EOL);
         	$evaluate = $line.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
         Log::info("1 existe try again 112");
            \SSH::into('checkpoint')->run($ssh_command, function($line){
            	Log::info($line.PHP_EOL);
            	$evaluate = $line.PHP_EOL;
            });
         }

         sleep(2);

         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
         	Log::info($line2.PHP_EOL);
         	$evaluate = $line2.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
            Log::info("1 existe try again 113");
            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            	Log::info($line2.PHP_EOL);
            	$evaluate = $line2.PHP_EOL;
            });
         }

         sleep(2);

         \SSH::into('checkpoint')->run($ssh_command3, function($line3){
         	Log::info($line3.PHP_EOL);
         	$evaluate = $line3.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
            Log::info("1 existe try again 116");
            \SSH::into('checkpoint')->run($ssh_command3, function($line3){
            	Log::info($line3.PHP_EOL);
            	$evaluate = $line3.PHP_EOL;
            });
         }

         sleep(2);

         \SSH::into('checkpoint')->run($ssh_command4, function($line4){
         	Log::info($line4.PHP_EOL);
         	$evaluate = $line4.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
            Log::info("1 existe try again 117");
            \SSH::into('checkpoint')->run($ssh_command4, function($line4){
            	Log::info($line4.PHP_EOL);
            	$evaluate = $line4.PHP_EOL;
            });
         }

         sleep(2);



 			$bd_ips_obj = DB::connection('checkpoint')->update("UPDATE ip_object_list SET ip_initial='".$request['new_ip_initial']."', ip_last='".$request['new_ip_last']."' WHERE object_id=".$object_id);

 			if($bd_ips_obj){
 				Log::info("Se actualizó ip object");
 			}else{
            Log::info("No se actualizó ip object");
 			}

         /*$curl = curl_init();

         curl_setopt_array($curl, array(
            CURLOPT_URL => "http://localhost:3500/new_object_ips",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$object_name\", \"ip_init\" : \"$new_ip_initial\", \"ip_last\" : \"$new_ip_last\", \r\n}",
            CURLOPT_HTTPHEADER => array(
            	"content-type: application/json",
            ),
         ));

         $response = curl_exec($curl);
         $err = curl_error($curl);

         curl_close($curl);

         if($err){
            Log::info("No se creó el primer rango");
         }else{*/

         $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";
         $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";
         $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";
         $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";


         \SSH::into('checkpoint')->run($ssh_command, function($line){
         	Log::info($line.PHP_EOL);
         	$evaluate = $line.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
         	Log::info("1 existe try again 112");
         	\SSH::into('checkpoint')->run($ssh_command, function($line){
         		Log::info($line.PHP_EOL);
         		$evaluate = $line.PHP_EOL;
         	});
         }

         sleep(2);

         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
         	Log::info($line2.PHP_EOL);
         	$evaluate = $line2.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
         	Log::info("1 existe try again 113");
         	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
         		Log::info($line2.PHP_EOL);
         		$evaluate = $line2.PHP_EOL;
         	});
         }

         sleep(2);

         \SSH::into('checkpoint')->run($ssh_command3, function($line3){
         	Log::info($line3.PHP_EOL);
         	$evaluate = $line3.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
         	Log::info("1 existe try again 116");
         	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
         		Log::info($line3.PHP_EOL);
         		$evaluate = $line3.PHP_EOL;
         	});
         }

         sleep(2);

         \SSH::into('checkpoint')->run($ssh_command4, function($line4){
         	Log::info($line4.PHP_EOL);
         	$evaluate = $line4.PHP_EOL;
         });

         $evaluate = $this->output;

         while (stripos($evaluate, "try again") !== false) {
         	Log::info("1 existe try again 117");
         	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
         		Log::info($line4.PHP_EOL);
         		$evaluate = $line4.PHP_EOL;
         	});
         }

         sleep(2);

         return response()->json([
				'success' => [
					'message' => "Datos actualizados correctamente",
					'status_code' => 200
				]
			]);
         //}
 	   }
  	}

   public function removeObjectComplete($object){

 		$emailCtrl = new EmailController;
      $evaluate = "";

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else	$sid = $this->getLastSession();

 		if($sid){

 			foreach($object as $value){
 				$object_name = $value['name'];
 				$object_id = $value['id'];

            Control::curl("172.16.3.114")
            ->is("delete-dynamic-object")
            ->config([
               'name' => $object_name,
               'layer' => 'Network'
            ])
            ->sid($sid)
            ->eCurl(function($response){
               $this->typeResponseCurl = 1;
            }, function($error){
               $this->typeResponseCurl = 0;
            });

 				if(!$this->typeResponseCurl){
 					$type = "error";
 					$update_rule = FwObject::where('id', $object_id)
 						->update(['status_error' => 1]);

 					$emailCtrl->sendEmailObject($object_name, $type);
 				}else{
 					$publish = $this->publishChanges($sid);

 					if($publish == "success"){

                  /*$curl = curl_init();

                  curl_setopt_array($curl, array(
                    	CURLOPT_URL => "http://localhost:3500/del_object",
                    	CURLOPT_RETURNTRANSFER => true,
                    	CURLOPT_ENCODING => "",
                    	CURLOPT_MAXREDIRS => 10,
                    	CURLOPT_TIMEOUT => 30,
                    	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    	CURLOPT_CUSTOMREQUEST => "POST",
                  	CURLOPT_SSL_VERIFYPEER => false,
                  	CURLOPT_SSL_VERIFYHOST => false,
                  	CURLOPT_POSTFIELDS => "{\r\n \"object_name\" : \"$object_name\"\r\n}",
                    	CURLOPT_HTTPHEADER => array(
                      	"content-type: application/json"
                    	),
                  ));

                  $response = curl_exec($curl);
                  $err = curl_error($curl);

                  curl_close($curl);

                  if($err){
                  	return response()->json([
                  		'error' => [
                  			'message' => $err,
                  			'status_code' => 20
                  		]
                  	]);
                  }else{*/
                  $ssh_command = 'tscpgw_api -g "172.16.3.112" -a deldyo -o '.$object_name;
                  $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$object_name;
                  $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a adddyo -o '.$object_name;
                  $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a adddyo -o '.$object_name;

                  \SSH::into('checkpoint')->run($ssh_command, function($line){
                  	Log::info($line.PHP_EOL);
                  	$evaluate = $line.PHP_EOL;
                  });

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 112");
                  	\SSH::into('checkpoint')->run($ssh_command, function($line){
                  		Log::info($line.PHP_EOL);
                  		$evaluate = $line.PHP_EOL;
                  	});
                  }

                  	sleep(2);

                  	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  		Log::info($line2.PHP_EOL);
                  		$evaluate = $line2.PHP_EOL;
                  	});

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 113");
                  	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  		Log::info($line2.PHP_EOL);
                  		$evaluate = $line2.PHP_EOL;
                  	});
                  }

                  sleep(2);

                  	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                  		Log::info($line3.PHP_EOL);
                  		$evaluate = $line3.PHP_EOL;
                  	});

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 116");
                  	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                  		Log::info($line3.PHP_EOL);
                  		$evaluate = $line3.PHP_EOL;
                  	});
                  }

                  sleep(2);

                  	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                  		Log::info($line4.PHP_EOL);
                  		$evaluate = $line4.PHP_EOL;
                  	});

                  $evaluate = $this->output;

                  while (stripos($evaluate, "try again") !== false) {
                  	Log::info("1 existe try again 117");
                  	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                  		Log::info($line4.PHP_EOL);
                  		$evaluate = $line4.PHP_EOL;
                  	});
                  }

                  sleep(2);


                  $delete = FwObject::where('id', '=', $object_id)->delete();

 						if($delete){
 							$delete_adds = AddressObject::where('object_id', '=', $object_id)->delete();
 							if($delete_adds){

 							}else{
 								$type = "error_ips";
 								$update_rule = AddressObject::where('id', $id_section)
 									->update(['status_error' => 1]);
 								$emailCtrl->sendEmailSection($name_section, $type);
 							}
 						}else{
 							$type = "error";
 							$update_rule = FwObject::where('id', $object_id)
 								->update(['status_error' => 1]);

 							$emailCtrl->sendEmailObject($object_name, $type);
 						}
                  //}
 					}else{
 						$type = "error";
 						$update_rule = FwObject::where('id', $object_id)
 							->update(['status_error' => 1]);

 						$emailCtrl->sendEmailObject($object_name, $type);
 					}
 				}
 			}
 		}else{
 			$type = "connection";
 			$update_rule = FwObject::where('id', $id_rule)
 				->update(['status_error' => 1]);

 			$emailCtrl->sendEmailObject($object_name, $type);
 		}
   }

   public function removeSectionComplete($section){
 		$emailCtrl = new EmailController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){

 			foreach($section as $value){

 				$id_section = $value['id'];
 				$name_section = $value['name'];

            Control::curl("172.16.3.114")
            ->is("delete-access-section")
            ->config([
               'name' => $name_section,
               'layer' => 'Network'
            ])
            ->sid($sid)
            ->eCurl(function(){
               $this->typeResponseCurl = 1;
            }, function(){
               $this->typeResponseCurl = 0;
            });

 				if(!$this->typeResponseCurl){
 					$type = "error";
 					$update_rule = FwSectionAccess::where('id', $id_section)
 						->update(['status_error' => 1]);

 					$emailCtrl->sendEmailSection($name_section, $type);

 				}else{
 					$publish = $this->publishChanges($sid);

 					if($publish == "success"){
 						$deleted_rule = FwSectionAccess::where('id', $id_section)->delete();

 					}else{
 						$type = "error";
 						$update_rule = FwSectionAccess::where('id', $id_section)
 							->update(['status_error' => 1]);

 						$emailCtrl->sendEmailSection($name_section, $type);
 					}
 				}
 			}

 		}else{
 			$type = "connection";
 			$update_rule = FwAccessRule::where('id', $id_rule)
 				->update(['status_error' => 1]);

 			$emailCtrl->sendEmailSection($name_section, $type);
 		}
  	}

   public function removeRuleComplete($rules){
 		$emailCtrl = new EmailController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){

 			foreach($rules as $value){

 				$uid_rule = $value['uid'];
 				$id_rule = $value['id'];
 				$name_rule = $value['name'];

            Control::curl("172.16.3.114")
            ->is("delete-access-rule")
            ->config([
               'uid' => $uid_rule,
               'layer' => 'Network'
            ])
            ->sid($sid)
            ->eCurl(function(){
               $this->typeResponseCurl = 1;
            }, function(){
               $this->typeResponseCurl = 0;
            });

 				if(!$this->typeResponseCurl){
 					$type = "error";
 					$update_rule = FwAccessRule::where('id', $id_rule)
 						->update(['status_error' => 1]);

 					$emailCtrl->sendEmailRules($name_rule, $type);

 				}else{
 					$publish = $this->publishChanges($sid);

 					if($publish == "success"){
 						$deleted_rule = FwAccessRule::where('id', $id_rule)->delete();

 					}else{
 						$type = "error";
 						$update_rule = FwAccessRule::where('id', $id_rule)
 							->update(['status_error' => 1]);

 						$emailCtrl->sendEmailRules($name_rule, $type);
 					}
 				}
 			}
 		}else{
 			$type = "connection";
 			$update_rule = FwAccessRule::where('id', $id_rule)
 				->update(['status_error' => 1]);

 			$emailCtrl->sendEmailRules($name_rule, $type);
 		}
   }

   public function getRules(Request $request){

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){
         Control::curl("172.16.3.114")
         ->is("show-access-rulebase")
         ->config([
            'name' => 'Network',
            'limit' => 100,
            'offset' => 0,
            'details-level' => 'full',
            'use-object-dictionary' => false
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
 				return response()->json([
 					'error' => [
 						'message' => $this->output,
 						'status_code' => 20
 					]
 				]);
 			}else{
 				$result = json_decode($this->output, true);
 				$data = [];
 				$data2 = [];
 				$i = 0;
 				$i2 = 0;

 				$user = JWTAuth::toUser($request['token']);
 				$company_id = $user['company_id'];
 				$role_user = $user->roles->first()->name;

 				$company_data = DB::table('fw_companies')->where('id', $company_id)->get();
 				$company_data2 = json_decode(json_encode($company_data), true);
 				$tag = $company_data2[0]['tag'];

 				foreach($result['rulebase'] as $key => $value){

 					$name_sep = explode("-", $value['name']);
 					$company_tag = isset($name_sep[1]) ? $name_sep[1] : 'none';

 					$data[$i]['section'] = array($value['name']);

 					foreach($value['rulebase'] as $key2 => $row){
 						$data[$i]['tag'] = $company_tag;

 						$data[$i]['name'] = $row['name'];
 						$data[$i]['uid'] = $row['uid'];
 						$data[$i]['type'] = $row['type'];
 						$data[$i]['comments'] = $row['comments'];
 						$data[$i]['enabled'] = $row['enabled'];
 						$data[$i]['rule_number'] = array($row['rule-number']);

 						foreach ($row['source'] as $value2) {
 							$data[$i]['source'][] = $value2['name'];
 						}

 						foreach ($row['destination'] as $value2) {
 							$data[$i]['destination'][] = $value2['name'];
 						}

 						foreach ($row['service'] as $value2) {
 							$data[$i]['service'][] = $value2['name'];
 						}

 						foreach ($row['vpn'] as $value2) {
 							$data[$i]['vpn'] = $value2['name'];
 						}

 						$data[$i]['action'] = $row['action']['name'];

 						$i++;
 					}
 				}

 				if($role_user != "superadmin"){
 					foreach ($data as $key => $value) {
 						if($value['tag'] == $tag){
 							array_push($data2, $value);
 						}
 						$i2++;
 					}

 					$data = $data2;
 				}

 				return response()->json([
 					'data' => $data,
 					//'data2' => $data2,
 					'status_code' => 200
 				]);
 			}
 		}else{
 			return response()->json([
 				'error' => [
 					'message' => 'error al obtener las políticas',
 					'status_code' => 20
 				]
 			]);
 		}
   }

   public function getRulesByCompany(Request $request){

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){
         Control::curl("172.16.3.114")
         ->is("show-access-rulebase")
         ->config([
            'offset' => 0,
            'limit' => 100,
            'name' => 'Network',
            'details-level' => 'full',
            'use-object-dictionary' => false
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
 				return response()->json([
 					'error' => [
 						'message' => $this->output,
 						'status_code' => 20
 					]
 				]);
 			}else{
 				$result = json_decode($this->output, true);
 				$data = [];
 				$data2 = [];
 				$i = 0;
 				$i2 = 0;

 				$user = JWTAuth::toUser($request['token']);
 				$company_id = $user['company_id'];
 				$role_user = $user->roles->first()->name;

 				$tag = $request['tag'];

 				foreach($result['rulebase'] as $key => $value){

 					$name_sep = explode("-", $value['name']);
 					$company_tag = isset($name_sep[1]) ? $name_sep[1] : 'none';

 					$data[$i]['section'] = array($value['name']);

 					foreach($value['rulebase'] as $key2 => $row){
 						$data[$i]['id'] = $i;
 						$data[$i]['tag'] = $company_tag;
 						$data[$i]['name'] = $row['name'];
 						$data[$i]['text'] = $row['name'];
 						$data[$i]['uid'] = $row['uid'];
 						//$data[$i]['type'] = $row['type'];
 						$data[$i]['comments'] = $row['comments'];
 						//$data[$i]['enabled'] = $row['enabled'];

 						$i++;
 					}
 				}

 				foreach ($data as $key => $value) {
 					if($value['tag'] == $tag && $value['comments'] == "editable"){
 						array_push($data2, $value);
 					}

 					$i2++;
 				}

 				$data = $data2;

 				return response()->json([
 					'data' => $data,
 					//'data2' => $data2,
 					'status_code' => 200
 				]);
 			}
 		}else{
 			return response()->json([
 				'error' => [
 					'message' => 'error al obtener las políticas',
 					'status_code' => 20
 				]
 			]);
 		}
   }

   public function evaluateChanges($info_changes){
 		$server_ch = 1;
 		$company_default = Company::findOrFail(1);
 		$evaluate;

 		foreach ($info_changes as $key => $value){
 			foreach ($value['data'] as $key2 => $row){

 				switch ($value['type_change']) {
 					case 'added-objects':

 						if($row['type'] == "dynamic-object"){
 							$new_object_name = $row['name'];
 							$object = DB::table('fw_objects')->where('name', '=', $new_object_name)->count();
 							Log::info($object);

 							if($object == 0){
 								Log::info("vuelve a entrar al object 0");

 								if(Session::has('sid_session')){
 									$sid = Session::get('sid_session');
 								}else{
 									$sid = $this->getLastSession();
 								}

 								if($sid){

 									$object_type = 4; //Es object dynamic

 									$object_new = New FwObject;
 									$object_new->name = $new_object_name;
 									$object_new->uid = $row['uid'];
 									$object_new->type_object_id = $object_type;
 									$object_new->server_id = $server_ch;
 									$object_new->company_id = $company_default->id;
 									$object_new->tag = $company_default->tag;
 									$object_new->editable = 1;

 									$object_new->save();

 									if($object_new->id){
 										Log::info("Se creó el objeto checkpoint");

 										$bd_obj_check = DB::connection('checkpoint')->table('object_list')->insertGetId(['name' => $new_object_name, 'description' => "Object creado desde checkpoint"]);

 										if($bd_obj_check){
 											Log::info("Se guardó en la bd checkpoint");
 										}else{
 											Log::info("No se guardó en bd checkpoint");
 										}

                              /*Control::ssh(['172.16.3.*',['112','113']])
                              ->addObject($new_object_name)
                              ->eSSH(function($response){}, true);
 										sleep(3);*/

                              /*$curl = curl_init();

                              curl_setopt_array($curl, array(
                                 CURLOPT_URL => "http://localhost:3500/new_object",
                                 CURLOPT_RETURNTRANSFER => true,
                                 CURLOPT_ENCODING => "",
                                 CURLOPT_MAXREDIRS => 10,
                                 CURLOPT_TIMEOUT => 30,
                                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                 CURLOPT_SSL_VERIFYPEER => false,
                                 CURLOPT_SSL_VERIFYHOST => false,
                                 CURLOPT_CUSTOMREQUEST => "POST",
                                 CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$new_object_name\"\r\n}",
                                 CURLOPT_HTTPHEADER => array(
                                 	"content-type: application/json",
                                 ),
                              ));

                              $response = curl_exec($curl);
                              $err = curl_error($curl);

                              curl_close($curl);

                              if($err){

                                 return response()->json([
                                    'error' => [
                                       'message' => "El objeto no pudo ser creado",
                                       'status_code' => 20
                                    ]
                                 ]);

                              }else{*/
                              $ssh_command = 'tscpgw_api -g "172.16.3.112" -a adddyo -o '.$new_object_name;
                              $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$new_object_name;
                              $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a adddyo -o '.$new_object_name;
                              $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a adddyo -o '.$new_object_name;

                              \SSH::into('checkpoint')->run($ssh_command, function($line){
                              	Log::info($line.PHP_EOL);
                              	$evaluate = $line.PHP_EOL;
                              });

                              $evaluate = $this->output;

                              while (stripos($evaluate, "try again") !== false) {
                              	Log::info("1 existe try again 112");
                              	\SSH::into('checkpoint')->run($ssh_command, function($line){
                              		Log::info($line.PHP_EOL);
                              		$evaluate = $line.PHP_EOL;
                              	});
                              }

                           	sleep(2);

                           	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                           		Log::info($line2.PHP_EOL);
                           		$evaluate = $line2.PHP_EOL;
                           	});

                              $evaluate = $this->output;

                              while (stripos($evaluate, "try again") !== false) {
                              	Log::info("1 existe try again 113");
                              	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                              		Log::info($line2.PHP_EOL);
                              		$evaluate = $line2.PHP_EOL;
                              	});
                              }

                              sleep(2);

                              	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                              		Log::info($line3.PHP_EOL);
                              		$evaluate = $line3.PHP_EOL;
                              	});

                              $evaluate = $this->output;

                              while (stripos($evaluate, "try again") !== false) {
                              	Log::info("1 existe try again 116");
                              	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                              		Log::info($line3.PHP_EOL);
                              		$evaluate = $line3.PHP_EOL;
                              	});
                              }

                              sleep(2);

                              	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                              		Log::info($line4.PHP_EOL);
                              		$evaluate = $line4.PHP_EOL;
                              	});

                              $evaluate = $this->output;

                              while (stripos($evaluate, "try again") !== false) {
                              	Log::info("1 existe try again 117");
                              	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                              		Log::info($line4.PHP_EOL);
                              		$evaluate = $line4.PHP_EOL;
                              	});
                              }

                              sleep(2);

                              $publish = $this->publishChanges($sid);

 										if($publish == 'success'){

 											$ip_initial = '1.1.1.1';
 											$ip_last = '1.1.1.1';

 											//Ingreso el rango de ip
 											$object_id = $object_new->id;
 											$type_address_id = 7;//Pertenece a rango de ip para checkpoint

                                 /*$curl = curl_init();

                                 curl_setopt_array($curl, array(
                                 	CURLOPT_URL => "http://localhost:3500/new_object_ips",
                                 	CURLOPT_RETURNTRANSFER => true,
                                 	CURLOPT_ENCODING => "",
                                 	CURLOPT_MAXREDIRS => 10,
                                 	CURLOPT_TIMEOUT => 30,
                                 	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                 	CURLOPT_SSL_VERIFYPEER => false,
                                 	CURLOPT_SSL_VERIFYHOST => false,
                                 	CURLOPT_CUSTOMREQUEST => "POST",
                                 	CURLOPT_POSTFIELDS => "{\r\n  \"object_name\" : \"$new_object_name\", \"ip_init\" : \"$ip_initial\", \"ip_last\" : \"$ip_last\", \r\n}",
                                 	CURLOPT_HTTPHEADER => array(
                                 		"content-type: application/json",
                                 	),
                                 ));

                                 $response = curl_exec($curl);
                                 $err = curl_error($curl);

                                 curl_close($curl);

                                 if($err){
                                    return response()->json([
                        					'error' => [
                        						'message' => "El objeto se creó pero no las Ips",
                        						'status_code' => 20
                        					]
                        				]);
                                 }else{*/
                                 $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
                                 $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
                                 $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
                                 $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";


                                 \SSH::into('checkpoint')->run($ssh_command, function($line){
                                 	Log::info($line.PHP_EOL);
                                 	$evaluate = $line.PHP_EOL;
                                 });

                                 $evaluate = $this->output;

                                 while (stripos($evaluate, "try again") !== false) {
                                 	Log::info("1 existe try again 112");
                                 	\SSH::into('checkpoint')->run($ssh_command, function($line){
                                 		Log::info($line.PHP_EOL);
                                 		$evaluate = $line.PHP_EOL;
                                 	});
                                 }

                                 sleep(2);

                                 \SSH::into('checkpoint')->run($ssh_command2, function($line2){
                                 	Log::info($line2.PHP_EOL);
                                 	$evaluate = $line2.PHP_EOL;
                                 });

                                 $evaluate = $this->output;

                                 while (stripos($evaluate, "try again") !== false) {
                                 	Log::info("1 existe try again 113");
                                 	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                                 		Log::info($line2.PHP_EOL);
                                 		$evaluate = $line2.PHP_EOL;
                                 	});
                                 }

                                 sleep(2);

                                 \SSH::into('checkpoint')->run($ssh_command3, function($line3){
                                 	Log::info($line3.PHP_EOL);
                                 	$evaluate = $line3.PHP_EOL;
                                 });

                                 $evaluate = $this->output;

                                 while (stripos($evaluate, "try again") !== false) {
                                 	Log::info("1 existe try again 116");
                                 	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                                 		Log::info($line3.PHP_EOL);
                                 		$evaluate = $line3.PHP_EOL;
                                 	});
                                 }

                                 sleep(2);

                                 \SSH::into('checkpoint')->run($ssh_command4, function($line4){
                                 	Log::info($line4.PHP_EOL);
                                 	$evaluate = $line4.PHP_EOL;
                                 });

                                 $evaluate = $this->output;

                                 while (stripos($evaluate, "try again") !== false) {
                                 	Log::info("1 existe try again 117");
                                 	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                                 		Log::info($line4.PHP_EOL);
                                 		$evaluate = $line4.PHP_EOL;
                                 	});
                                 }

                                 sleep(2);

                                 Log::info("ip agregada ch");
 											$addr_obj = new AddressObject;
 											$addr_obj->ip_initial = $ip_initial;
 											$addr_obj->ip_last = $ip_last;
 											$addr_obj->object_id = $object_id;
 											$addr_obj->type_address_id = $type_address_id;
 											$addr_obj->save();

 											if($addr_obj){
 												$bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $bd_obj_check, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()]);

 												if($bd_ips_check){
 													Log::info("Objeto agregado correctamente");
 												}else{
 													Log::info("Se creó el objeto pero no las ips");
 												}
 											}else{
 												Log::info("Se creó el objeto pero no las ips");
 											}
                                 //}
 										}else{
 											Log::info("El objeto no pudo ser creado");
 										}
                              //}
 									}else{
 										Log::info("El objeto no pudo ser creado");
 									}
 								}else{
 									Log::info("No se pudo agregar el objeto");
 								}
 							}else{
 								Log::info("No se agregó objeto porque ya existe");
 							}
 						}elseif($row['type'] == "access-rule"){
 							$rule_name = $row['name'];

 							$rules = DB::table('fw_access_rules_ch')->where('name', '=', $rule_name)->count();

 							if($rules == 0){
 								$rule = new FwAccessRule;
 								$rule->name = $rule_name;
 								$rule->uid = $row['uid'];
 								$rule->company_id = $company_default->id;
 								$rule->tag = $company_default->tag;
 								//$rule->section_id = $section_id;
 								$rule->section_id = 1;
 								$rule->editable = 1;
 								$rule->save();
 							}else{
 								Log::info("Ya existe la regla");
 							}

 						}elseif ($row['type'] == "access-section") {
 							$name_section = $row['name'];

 							$sections = DB::table('fw_access_sections_ch')->where('name', '=', $name_section)->count();

 							if($sections == 0){
 								$section = new FwSectionAccess;
 								$section->name = $name_section;
 								$section->company_id = $company_default->id;
 								$section->tag = $company_default->tag;
 								$section->save();
 							}else{
 								Log::info("Ya existe la sección");
 							}
 						}

 					break;

 					case 'modified-objects':
 						foreach ($row as $key2 => $val) {
 							$new_name = $row['new-object']['name'];

 							if($val['type'] == "dynamic-object"){

 								if($key2 == "old-object"){
 									$old_name = $val['name'];

 									$object = DB::table('fw_objects')->where('name', '=', $old_name)->get();
 									$object = json_decode(json_encode($object), true);

 									if(count($object) > 0){
 										foreach ($object as $obj){
 											$id_object = $obj['id'];
 										}

 										$upd_obj = DB::table('fw_objects')->where('id', '=', $id_object)->update(['name' => $new_name]);

 										if($upd_obj){
 											Log::info("se actualizó objeto");
 										}else{
 											Log::info("NO se actualizó objeto");
 										}
 									}
 								}
 							}elseif($val['type'] == "access-section"){

 								if($key2 == "old-object"){
 									$old_name = $val['name'];

 									$section = DB::table('fw_access_sections_ch')->where('name', '=', $old_name)->get();
 									$section = json_decode(json_encode($section), true);

 									if(count($section) > 0){
 										foreach ($section as $sect){
 											$section_id = $sect['id'];
 										}

 										$upd_sec = DB::table('fw_access_sections_ch')->where('id', '=', $section_id)->update(['name' => $new_name]);

 										if($upd_sec){
 											Log::info("Se actualizó sección");
 										}else{
 											Log::info("NO se actualizó sección");
 										}
 									}
 								}

 							}elseif($val['type'] == "access-rule"){

 								if($key2 == "old-object"){

 									$old_name = $val['name'];

 									$rule = DB::table('fw_access_rules_ch')->where('name', '=', $old_name)->get();
 									$rule = json_decode(json_encode($rule), true);

 									if(count($rule) > 0){
 										foreach ($rule as $rul){
 											$rule_id = $rul['id'];
 										}

 										$upd_rule = DB::table('fw_access_rules_ch')->where('id', '=', $rule_id)->update(['name' => $new_name]);

 										if($upd_rule){
 											Log::info("Se actualizó regla");
 										}else{
 											Log::info("NO se actualizó regla");
 										}
 									}
 								}
 							}
 						}

 					break;

 					case 'deleted-objects':

 						if($row['type'] == "dynamic-object"){

 							$old_name = $row['name'];

 							$object = DB::table('fw_objects')->where('name', '=', $old_name)->get();
 							$count_obj = count($object);

 							if($count_obj > 0){
 								$object = json_decode(json_encode($object), true);
 								foreach ($object as $obj){
 									$id_object = $obj['id'];
 								}

 								$del_obj = DB::table('fw_objects')->where('id', '=', $id_object)->delete();

 								if($del_obj){
 									/*$curl = curl_init();

                           curl_setopt_array($curl, array(
                             	CURLOPT_URL => "http://localhost:3500/del_object",
                             	CURLOPT_RETURNTRANSFER => true,
                             	CURLOPT_ENCODING => "",
                             	CURLOPT_MAXREDIRS => 10,
                             	CURLOPT_TIMEOUT => 30,
                             	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                             	CURLOPT_CUSTOMREQUEST => "POST",
                           	CURLOPT_SSL_VERIFYPEER => false,
                           	CURLOPT_SSL_VERIFYHOST => false,
                           	CURLOPT_POSTFIELDS => "{\r\n \"object_name\" : \"$old_name\"\r\n}",
                             	CURLOPT_HTTPHEADER => array(
                               	"content-type: application/json"
                             	),
                           ));

                           $response = curl_exec($curl);
                           $err = curl_error($curl);

                           curl_close($curl);

                           if($err){
                           	Log::info("No se eliminó con script");
                           }else{
                              Log::info("se eliminó objeto");
                           }*/

                           $ssh_command = 'tscpgw_api -g "172.16.3.112" -a deldyo -o '.$object_name;
                           $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$object_name;
                           $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a adddyo -o '.$object_name;
                           $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a adddyo -o '.$object_name;

                           \SSH::into('checkpoint')->run($ssh_command, function($line){
                           	Log::info($line.PHP_EOL);
                           	$evaluate = $line.PHP_EOL;
                           });

                           $evaluate = $this->output;

                           while (stripos($evaluate, "try again") !== false) {
                           	Log::info("1 existe try again 112");
                           	\SSH::into('checkpoint')->run($ssh_command, function($line){
                           		Log::info($line.PHP_EOL);
                           		$evaluate = $line.PHP_EOL;
                           	});
                           }

                           	sleep(2);

                           	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                           		Log::info($line2.PHP_EOL);
                           		$evaluate = $line2.PHP_EOL;
                           	});

                           $evaluate = $this->output;

                           while (stripos($evaluate, "try again") !== false) {
                           	Log::info("1 existe try again 113");
                           	\SSH::into('checkpoint')->run($ssh_command2, function($line2){
                           		Log::info($line2.PHP_EOL);
                           		$evaluate = $line2.PHP_EOL;
                           	});
                           }

                           sleep(2);

                           	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                           		Log::info($line3.PHP_EOL);
                           		$evaluate = $line3.PHP_EOL;
                           	});

                           $evaluate = $this->output;

                           while (stripos($evaluate, "try again") !== false) {
                           	Log::info("1 existe try again 116");
                           	\SSH::into('checkpoint')->run($ssh_command3, function($line3){
                           		Log::info($line3.PHP_EOL);
                           		$evaluate = $line3.PHP_EOL;
                           	});
                           }

                           sleep(2);

                           	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                           		Log::info($line4.PHP_EOL);
                           		$evaluate = $line4.PHP_EOL;
                           	});

                           $evaluate = $this->output;

                           while (stripos($evaluate, "try again") !== false) {
                           	Log::info("1 existe try again 117");
                           	\SSH::into('checkpoint')->run($ssh_command4, function($line4){
                           		Log::info($line4.PHP_EOL);
                           		$evaluate = $line4.PHP_EOL;
                           	});
                           }

                           sleep(2);

 								}else{
 									Log::info("NO se eliminó objeto");
 								}
 							}

 						}elseif($row['type'] == "access-section"){

 							$old_name = $row['name'];

 							$section = DB::table('fw_access_sections_ch')->where('name', '=', $old_name)->get();
 							$count_sect = count($section);

 							if($count_sect > 0){
 								$section = json_decode(json_encode($section), true);
 								foreach ($section as $sect){
 									$section_id = $sect['id'];
 								}

 								$del_sec = DB::table('fw_access_sections_ch')->where('id', '=', $section_id)->delete();

 								if($del_sec){
 									Log::info("Se eliminó sección");
 								}else{
 									Log::info("NO se eliminó sección");
 								}
 							}

 						}elseif($row['type'] == "access-rule"){

 							$old_name = $row['name'];

 							$rule = DB::table('fw_access_rules_ch')->where('name', '=', $old_name)->get();
 							$count_rule = count($rule);

 							if($count_rule > 0){
 								$rule = json_decode(json_encode($rule), true);
 								foreach ($rule as $rul){
 									$rule_id = $rul['id'];
 								}

 								$del_rule = DB::table('fw_access_rules_ch')->where('id', '=' ,$rule_id)->delete();

 								if($del_rule){
 									Log::info("se eliminó regla");
 								}else{
 									Log::info("NO se eliminó regla");
 								}
 							}
 						}

 					break;

 					default:
 						# code...
 					break;
 				}
 			}
 		}
   }

   public function addRules($data){
      $checkpoint2 = new CheckPointFunctionController;

 		Log::info("DATA ADDRULES");
 		Log::info($data);

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
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
				CURLOPT_URL => "https://172.16.3.114/web_api/add-access-rule",
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
			Log::info("RESPUESTA ADD RULES-----------------------------------");
			//Log::info(print_r($response, true));
			sleep(3);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return "error";
			}else{
 				$publish2 = $this->publishChanges($sid);

 				if($publish2 == 'success'){

 					$result = json_decode($response, true);
 					Log::info($result);

 					if(isset($result['code'])){
 						Log::info($result['code']);
 						return response()->json([
 							'error' => [
 								'message' => $result['message'],
 								'status_code' => 20,
 								'error' => "Existe error en checkpoint"
 							]
 						]);
 					}else{

 						$uid_rule = $result['uid'];

 						$rule = new FwAccessRule;
 						$rule->name = $rule_name;
 						$rule->uid = $uid_rule;
 						$rule->company_id = $company_id;
 						$rule->tag = $tag;
 						$rule->section_id = $section_id;
 						$rule->editable = 0;
 						$rule->save();

 						if($rule->id){

 							$rule_objects = new CheckPointRulesObjects;
 							$rule_objects->rule_id = $rule->id;
 							$rule_objects->object_src = $src;
 							$rule_objects->object_dst = $dst;
 							$rule_objects->save();

 							return "success";
 						}else{
 							#Log::info("Se creó la regla ".$rule_name." pero no se guardó");
 							return "success";
 						}
 					}
 				}else{
 					return "error";
 				}
 			}
 		}else{
 			return "error";
 		}
   }

   /*********AQUI HAY QUE SEGUIR*********/
   public function addNewRule(Request $request){

      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session')) $sid = Session::get('sid_session');
 		else $sid = $this->getLastSession();

 		if($sid){

 			$rule_name = $request['name'];
 			$src = $request['source'];
 			$dst = $request['destination'];
 			$vpn = $request['vpn'];
 			$action = $request['action'];
 			$company_id = $request['company_id'];

 			$section_company = FwSectionAccess::where('company_id', $company_id)->get();

 			Log::info($section_company);
 			$name_section = $section_company[0]['name'];
 			$tag = $section_company[0]['tag'];
 			$section_id = $section_company[0]['id'];

 			$rule_name = "CUST-".$tag."-$rule_name";

         Control::curl("172.16.3.114")
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
 				return response()->json([
 					'error' => [
 						'message' => $this->output,
 						'status_code' => 20
 					]
 				]);
 			}else{
            $rsp = $this->output;
 				$publish2 = $this->publishChanges($sid);

 				if($publish2 == 'success'){

               $newrule2 = $checkpoint2->addNewRule2($request);
               sleep(3);

 					$result = json_decode($rsp, true);
 					Log::info($result);

 					if(isset($result['code'])){
 						Log::info($result['code']);
 						return response()->json([
 							'error' => [
 								'message' => $result['message'],
 								'status_code' => 20,
 								'error' => "Existe error en checkpoint"
 							]
 						]);
 					}else{

 						/*$install = $this->installPolicy();
                  Log::info($install);*/

 						$uid_rule = $result['uid'];

 						$rule = new FwAccessRule;
 						$rule->name = $rule_name;
 						$rule->uid = $uid_rule;
 						$rule->company_id = $company_id;
 						$rule->tag = $tag;
 						$rule->section_id = $section_id;
 						$rule->editable = 1;
 						$rule->save();

                  $rule_objects = new CheckPointRulesObjects;
                  $rule_objects->rule_id = $rule->id;
                  $rule_objects->object_src = $src;
                  $rule_objects->object_dst = $dst;
                  $rule_objects->save();

 						if($rule->id){ //&& $install == "success"

 							return response()->json([
 								'success' => [
 									'message' => "Regla creada exitosamente",
 									'status_code' => 200
 								]
 							]);
 						}else{
 							return response()->json([
 								'success' => [
 									'message' => "Se creó la regla ".$rule_name." pero no se guardó en la db.",
 									'status_code' => 200
 								]
 							]);
 						}
 					}
 				}else{
 					return response()->json([
 						'error' => [
 							'message' => "error",
 							'status_code' => 20
 						]
 					]);
 				}
 			}
 		}else{
 			return response()->json([
 				'error' => [
 					'message' => "No existe sesión con el checkpoint",
 					'status_code' => 20
 				]
 			]);
 		}
   }

   public function IpsByRange(Request $request){

      $range_id = $request['address_id'];

      $ips = DB::table('fw_address_objects AS addr')
         ->join('fw_objects AS obj', 'addr.object_id', '=', 'obj.id')
         ->where('addr.id', '=', $range_id)
         ->select('addr.id AS address_id', 'addr.ip_initial', 'addr.ip_last', 'obj.name', 'obj.id AS object_id')
         ->get();

      $ips = json_decode(json_encode($ips), true);
      $ip_list = [];
      $ip_array = [];

      foreach ($ips as $key => $value) {
         if($value['ip_initial'] == '1.1.1.1'){
            unset($ips[$key]);
         }else{
            $networks = Range::parse($value['ip_initial'].'-'.$value['ip_last']);
            foreach($networks as $network){
               $ip_array['address'] = (string)$network;
               $ip_array['address_id'] = $value['address_id'];
               $ip_array['object_name'] = $value['name'];
               $ip_array['object_id'] = $value['object_id'];
               $ip_array['ip_initial'] = $value['ip_initial'];
               $ip_array['ip_last'] = $value['ip_last'];
               array_push($ip_list, $ip_array);
            }
         }
      }

      return response()->json([
         'success' => [
           'data' => $ip_list,
           'status_code' => 200
         ]
      ]);
   }

   public function evaluateObjectsChanges(){

      $date_init = \Carbon\Carbon::now()->subMinute(5);
      $date_last = \Carbon\Carbon::now();

      $date_init = $date_init->toIso8601ZuluString();
      $date_last = $date_last->toIso8601ZuluString();

      $date_init = str_replace('Z', '', $date_init);
      $date_last = str_replace('Z', '', $date_last);
      Log::info($date_init.' '.$date_last);

      if(Session::has('sid_session')) $sid = Session::get('sid_session');
      else $sid = $this->getLastSession();

      Control::curl("172.16.3.114")
      ->is('show-changes')
      ->config([
         'from-date' => '2018-09-26T11:30:51',
         'to-date' => '2018-09-26T11:55:51'
      ])
      ->sid($sid)
      ->eCurl(function($response){
         $this->output = $response;
         $this->typeResponseCurl = 1;
      });

      $resp = json_decode($this->output, true);

      if(isset($resp['task-id'])){
         $task = $resp['task-id'];
         sleep(2);
         $result_task = $this->showTask($task, $sid);
         $array_tasks = [];
         $i = 0;

         foreach ($result_task['tasks'] as $key => $value) {
            foreach ($value['task-details'] as $key2 => $value2) {
               foreach($value2['changes'] as $row){
                  $array_tasks[$i] = array_filter($row['operations']);
                  $i++;
               }
            }
         }

         $i = 0;
         $info_changes = [];
         foreach ($array_tasks as $key => $value) {
            foreach ($value as $key2 => $value2) {
               $info_changes[$i]['type_change'] = $key2;
               $info_changes[$i]['data'] = $value2;

               $i++;
            }
         }
         Log::info($info_changes);
         //return "success";
         //return $array_tasks;
      }elseif(isset($resp['code'])){
         Log::info($resp['message']);

      }
      else{
         Log::info($resp['message']);
         //return "error";
      }

   }

}
