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

class CheckpointController extends Controller
{
    private $output = "";
    private $typeResponseCurl = 1;
    public function test(){
        /*return Control::curl("172.16.3.114")
        ->config(array(
            "user" => "test",
            "password" => "test"
        ))->eCurl();*/
        return 'ok';

    }
    //Comienza desde aqui
    public function __construct(){
    		Session::forget('sid_session');
    		$evaluate = "";
  	}
    public function servers(){
        $server = FwServer::where('id', 1)->get();
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
                    'message' => $err,
                    'status_code' => 20
                ]
            ]);
        }else{
            $result = json_decode($this->output, true);
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
        Control::curl("172.16.3.114")
          ->is('publish')
          ->sid($sid)
          ->eCurl(function($response){
              $this->output = $response;
              $this->typeResponseCurl = 1;
          }, function($error){
              $this->output = $error;
              $this->typeResponseCurl = 0;
        });
    		if(!$this->typeResponseCurl) return "error";
    		else return "success";
  	}
    public function discardChanges(){
    		$sid = Session::get('sid_session');
        return Control::curl("172.16.3.114")
          ->is('discard')
          ->sid($sid)
          ->eCurl();
    }
    public function installPolicy(){
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
                'target' => ['CLUSTER-IP-REPUTATION']
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
            if(isset($resp['task-id'])){
                $task = $resp['task-id'];
                $result_task = $this->showTask($task);

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
  	}
    public function getIpsByObject(Request $request){
    		$object_id = $request['object_id'];
    		$ips = DB::table('fw_address_objects')
    			->join('fw_objects', 'fw_address_objects.object_id', '=', 'fw_objects.id')
    			->where('object_id', $object_id)
    			->select('fw_address_objects.*', 'fw_objects.name as objeto')
    			->get();
    		return response()->json([
    			'data' => $ips,
    			'object_id' => $object_id
    		]);
    }
    public function assignIpObject(Request $request){
    		$object_id = $request['object_id'];

    		$object = DB::table('fw_objects')->where('id', $object_id)->get();
    		$object = json_decode(json_encode($object), true);

    		$object_name = $object[0]['name'];
    		$ip_initial = $request['ip_initial'];
    		$ip_last = $request['ip_last'];

    		$type_address_id = 7;//Pertenece a rango de ip para checkpoint

        Control::ssh(['172.16.3.*', ['112','113']])
          ->addIPRange($object_name, $ip_initial, $ip_last)
          ->eSSH(function($response){}, true);
        sleep(3);

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
      						'message' => $err,
      						'status_code' => 20
      					]
      				]);
    			}else{
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
    		if(Session::has('sid_session'))
    			$sid = Session::get('sid_session');
    		else $sid = $this->getLastSession();
    		if($sid){
      			$name_section = 'CUST-'.$tag;
      			#$rule_name = $request['rule_name'];
            Control::curl("172.16.3.114")
              ->is('add-access-section')
              ->config([
                  'layer' => 'Network',
                  'name' => $name_section,
                  'position' => [
                      'below' => 'DATACENTER NETWORKS'
                  ]
              ])
              ->sid($sid)
              ->eCurl(function($response){
                  $this->typeResponseCurl = 1;
              }, function($error){
                  $this->typeResponseCurl = 0;
              });
      			if($this->typeResponseCurl){
        				/*return response()->json([
        					'error' => [
        						'message' => $err,
        						'status_code' => 20
        					]
        				]);*/
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
        						//Log::info("se creo la sección ".$name_section." pero no se guardó");
        						return [1, $name_section];
        					}
        				}else{
        					/*return response()->json([
        						'message' => "error",
        						'status_code' => 20
        					]);*/
        					return "error";
        				}
      			}
    		}else{
      			/*return response()->json([
      				'message' => "error",
      				'status_code' => 20
      			]);*/
      			return "error";
    		}
  	}
    public function addObjectsToRule(Request $request){
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

            Control::curl("172.16.3.114")
              ->config([
                  'uid' => $uid_rule,
                  'layer' => 'Network',
                  $field_change => $data_field2
              ])
              ->sid($sid)
              ->eCurl(function($response){
                  $this->output = $response;
                  $this->typeResponseCurl = 1;
              }, function($error){
                  $this->output = $response;
                  $this->typeResponseCurl = 0;
              });
      			if (!$this->typeResponseCurl){
        				return response()->json([
          					'error' => [
            						'message' => $err,
            						'status_code' => 20
        					]
        				]);
      			}else{
        				$result = json_decode($this->output, true);
        				//Log::info(print_r($result, true));
        				$publish = $this->publishChanges($sid);
        				if($publish == "success") return response()->json([
          						'message' => "success",
          					]);
        				else return response()->json([
          						'message' => "error",
          					]);
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
                -eCurl(function($response){
                    $this->output = $response;
                    $this->typeResponseCurl = 1;
                }, function($error){
                    $this->output = $response;
                    $this->typeResponseCurl = 0;
                });
      				if (!$this->typeResponseCurl) {
      				    return $err;
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
}
