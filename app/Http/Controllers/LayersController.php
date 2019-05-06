<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
use App\LayerSecurity;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;

use App\Http\Requests;
use App\Http\Controllers\CheckpointController;
use File;
use Illuminate\Foundation\Bus\DispatchesJobs;
use GeoIP as GeoIP;

class LayersController extends Controller
{

	private $output;

   	public function __construct(){
		$evaluate = "";
	}

	public function getObjectByServers(Request $request){

      //$data = getObjectServers($request['token']);

		$list = DB::table('objects_list_block')
		    	->where('visible', '=', 1)
		    	->get();

		$list = json_decode(json_encode($list), true);

      return response()->json($list);
	}

	public function getIpsList(Request $request){

		//$list = LayerSecurity::join('fw_servers', 'fw_servers.id', '=', 'layers_security_list.server_id')->get();
		$list = DB::table('layers_security_list')
		    	->join('fw_servers', 'fw_servers.id', '=', 'layers_security_list.server_id')
				->where('name_object', '=', 'checkpoint-block')
		    	->select('layers_security_list.*', 'fw_servers.name AS name_server')
		    	->get();

		$list = json_decode(json_encode($list), true);

		$array = [];

		foreach($list as $value){
			$value['id'] = $value['id'];
			$value['name_object'] = $value['name_object'];
			$value['name_server'] = $value['name_server'];
			$value['comment'] = $value['comment'];
			$value['ip_initial'] = $value['ip_initial'];
			$value['ip_last'] = $value['ip_last'];
			$value['server_id'] = $value['server_id'];
			$value['status'] = $value['status'];
			$value['status_num'] = $value['status_num'];
			$value['created_at'] = $value['created_at'];
			$value['updated_at'] = $value['updated_at'];
			$value['deleted_at'] = $value['deleted_at'];
			$value['country'] = @geoip_country_name_by_name($value['ip_initial']);

			array_push($array, $value);
		}

		return response()->json([
			'data' => $array
		]);
	}

	public function getIpsListSocBlock(Request $request){

		$list = DB::table('layers_security_list')
				->join('fw_servers', 'fw_servers.id', '=', 'layers_security_list.server_id')
				->where('name_object', '=', 'soc-5g-block')
				->select('layers_security_list.*', 'fw_servers.name AS name_server')
		    	->get();

		$list = json_decode(json_encode($list), true);

		$array = [];

		foreach($list as $value){
			$value['id'] = $value['id'];
			$value['name_object'] = $value['name_object'];
			$value['name_server'] = $value['name_server'];
			$value['comment'] = $value['comment'];
			$value['ip_initial'] = $value['ip_initial'];
			$value['ip_last'] = $value['ip_last'];
			$value['server_id'] = $value['server_id'];
			$value['status'] = $value['status'];
			$value['status_num'] = $value['status_num'];
			$value['created_at'] = $value['created_at'];
			$value['updated_at'] = $value['updated_at'];
			$value['deleted_at'] = $value['deleted_at'];
			$value['country'] = @geoip_country_name_by_name($value['ip_initial']);

			array_push($array, $value);
		}

		return response()->json([
			'data' => $array
		]);
	}

	public function getIpsListSocAllow(Request $request){

		$list = DB::table('layers_security_list')
				->join('fw_servers', 'fw_servers.id', '=', 'layers_security_list.server_id')
				->where('name_object', '=', 'soc-5g-allow')
				->select('layers_security_list.*', 'fw_servers.name AS name_server')
		    	->get();

		$list = json_decode(json_encode($list), true);

		$array = [];

		foreach($list as $value){
			$value['id'] = $value['id'];
			$value['name_object'] = $value['name_object'];
			$value['name_server'] = $value['name_server'];
			$value['comment'] = $value['comment'];
			$value['ip_initial'] = $value['ip_initial'];
			$value['ip_last'] = $value['ip_last'];
			$value['server_id'] = $value['server_id'];
			$value['status'] = $value['status'];
			$value['status_num'] = $value['status_num'];
			$value['created_at'] = $value['created_at'];
			$value['updated_at'] = $value['updated_at'];
			$value['deleted_at'] = $value['deleted_at'];
			$value['country'] = @geoip_country_name_by_name($value['ip_initial']);

			array_push($array, $value);
		}

		return response()->json([
			'data' => $array
		]);
	}

	public function addIpList(Request $request, CheckpointController $checkpoint){

		Log::info($request);

		$validateCmd = new ValidateCommandController;
      #$ip_initial = $request['ip_initial'];
      #$ip_last = $ip_initial;
      $comment = $request['comment'];
      $name_object = $request['object_name'];
      $server_id = $request['server_id'];
		$evaluate = "";

		$userLog = JWTAuth::toUser($request['token']);
		$api_token = $userLog['api_token'];
		$company_id = $userLog['company_id'];
		$company_data = DB::table('fw_companies')->where('id', $company_id)->get();
		$company_data2 = json_decode(json_encode($company_data), true);

		$name_company = $company_data2[0]['name'];
		$token_company = $company_data2[0]['token_company'];
		$arreglo_data = [];

		//EVALUAR ARCHIVO JSON
		$path = storage_path() ."/app/".$name_company."/".$token_company.".json";

		if(File::exists($path)){
		 	$data_exist = json_decode(file_get_contents($path), true);
		 	Log::info($data_exist);
		} else {
		 	Log::info("NO EXISTE FILE");
		 	$arreglo = array("success" => "", "error" => "", "info" => 0);

		 	$json = json_encode($arreglo);
		 	\Storage::put($name_company.'/'.$token_company.'.json', $json);
		}

		foreach($request['ips'] as $value){
			$ip_initial = $value['inicial'];
			$ip_last = $value['final'];

			$xnumber = $ip_initial;
	      // Redes que nos pertenecen
	      $subnet='38.103.38';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='38.118.71';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='190.120.9';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='190.120.8';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='190.120.12';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='190.120.26';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='190.120.4';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='190.120.15';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='200.13.160';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='192.168.1';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='10.10.0';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='172.16';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' pertenece al rango '.$subnet.'.0 que es una red propia.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='0.0.0.0';
	      if(preg_match("/^$subnet$/", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' es privilegiada!');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' es privilegiada.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='255.255.255.255';
	      if(preg_match("/^$subnet$/", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' es privilegiada!');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' es privilegiada.',
	               'status_code' => 20
	            ]
	         ]);
	      }

	      $subnet='127.0.0';
	      if(preg_match("/^$subnet./", $xnumber)){
	         $flag = false;
	         Log::info('Error! La IP '.$xnumber.' es privilegiada!');
	         return response()->json([
	            'error' => [
	               'message' => 'Error! La IP '.$xnumber.' es privilegiada.',
	               'status_code' => 20
	            ]
	         ]);
	      }

			//Verifico si no existe la ip a agregar
			$exist_row = LayerSecurity::where('ip_initial', '=', $ip_initial)->count();

			if($exist_row == 0){

				$list_sec = new LayerSecurity;
		      $list_sec->name_object = $name_object;
		      $list_sec->ip_initial = $ip_initial;
		      $list_sec->ip_last = $ip_last;
		      $list_sec->comment = $comment;
		      $list_sec->server_id = $server_id;
		      $list_sec->save();

		      if($list_sec){

		         $object_list = DB::connection('checkpoint')->select('SELECT * FROM object_list WHERE name="'.$name_object.'"');
		         $object_list = json_decode(json_encode($object_list), true);

		         Log::info($object_list);

		         foreach ($object_list as $value){
		            $object_id_list = $value['id'];
		         }

		         $bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $object_id_list, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()]);

		         Log::info($bd_ips_check);

		         if($bd_ips_check){
						$total_ips = 1;
						$flag = 1;

						//DEBO MANDAR A LA VERIFICACION CADA IP
						$validateAdddyo = $validateCmd->validateAssignIpObject($name_object, $ip_initial, $ip_last, $total_ips, $flag);

						array_push($arreglo_data, $validateAdddyo);

					 	if(!empty($data_exist)){
					    	foreach ($data_exist as $value) {
					       	Log::info("VALUE DE DATA EXIST");
					       	Log::info($value);
					    	}
					 	}

					 	$json = json_encode($arreglo_data);
					 	\Storage::put($name_company.'/'.$token_company.'.json', $json);

						return response()->json([
							'success' => [
								'message' => "Datos ingresados correctamente",
								'status_code' => 200
							]
						]);
		         }else{
						Log::info("No se guardó en el checkpoint, solo localmente!");
		            return response()->json([
		               'error' => [
		                  'message' => "No se guardó en el checkpoint, solo localmente!",
		                  'status_code' => 20
		               ]
		            ]);
		         }
		      }else{
					Log::info("No se guardaron los datos");
		         return response()->json([
		            'error' => [
		               'message' => "No se guardaron los datos",
		               'status_code' => 20
		            ]
		         ]);
		      }
			}else{
				return response()->json([
					'error' => [
						'message' => "¡La IP solicitada ya existe!",
						'status_code' => 20
					]
				]);
			}
		}//fin foreach
   }

   public function removeIpList(Request $request, CheckpointController $checkpoint){

		$validateCmd = new ValidateCommandController;
      $object_name = $request['name_object'];
      $ip_initial = $request['ip_initial'];
      $ip_last = $ip_initial;
      $id_list = $request['id'];
		$evaluate = "";

		$total_ips = 1;
		$flag = 0;

		/*$userLog = JWTAuth::toUser($request['token']);
		$api_token = $userLog['api_token'];
		$company_id = $userLog['company_id'];
		$company_data = DB::table('fw_companies')->where('id', $company_id)->get();
		$company_data2 = json_decode(json_encode($company_data), true);

		$name_company = $company_data2[0]['name'];
		$token_company = $company_data2[0]['token_company'];
		$arreglo_data = [];

		//EVALUAR ARCHIVO JSON
		$path = storage_path() ."/app/".$name_company."/".$token_company.".json";

		if(File::exists($path)){
		 	$data_exist = json_decode(file_get_contents($path), true);
		 	Log::info($data_exist);
		} else {
		 	Log::info("NO EXISTE FILE");
		 	$arreglo = array("success" => "", "error" => "", "info" => 0);

		 	$json = json_encode($arreglo);
		 	\Storage::put($name_company.'/'.$token_company.'.json', $json);
		}

		$validateDelrip = $validateCmd->validateRemoveIpObject($object_name, $ip_initial, $ip_last, $total_ips, $flag);

		array_push($arreglo_data, $validateDelrip);

		if(!empty($data_exist)){
			foreach ($data_exist as $value) {
				Log::info("VALUE DE DATA EXIST");
				Log::info($value);
			}
		}

		$json = json_encode($arreglo_data);
		\Storage::put($name_company.'/'.$token_company.'.json', $json);

		sleep(2);*/

		$ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
		$ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
		$ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
		$ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

		\SSH::into('checkpoint')->run($ssh_command, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
			Log::info($line2.PHP_EOL);
			$evaluate = $line2.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
				Log::info($line2.PHP_EOL);
				$evaluate = $line2.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
			Log::info($line3.PHP_EOL);
			$evaluate = $line3.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
				Log::info($line3.PHP_EOL);
				$evaluate = $line3.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
			Log::info($line4.PHP_EOL);
			$evaluate = $line4.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
				Log::info($line4.PHP_EOL);
				$evaluate = $line4.PHP_EOL;
			});
		}

		sleep(2);

      $delete_list = DB::table('layers_security_list')->where('id', '=', $id_list)->delete();

      if($delete_list){
         $object_list = DB::connection('checkpoint')->select('SELECT * FROM ip_object_list WHERE ip_initial="'.$ip_initial.'" AND ip_last="'.$ip_last.'"');
         $object_list = json_decode(json_encode($object_list), true);

         foreach($object_list as $row){
            $id_obj_list = $row['id'];
         }

			if(isset($id_obj_list)){
				$delete_add_ch = DB::connection('checkpoint')->delete("DELETE FROM ip_object_list WHERE id=".$id_obj_list);
			}else{
				$delete_add_ch = 1;
			}

         if($delete_add_ch){
            return response()->json([
               'success' => [
                  'data' => 'Rango de ips eliminado',
                  'status_code' => 200
               ]
            ]);
         }else{
            return response()->json([
               'error' => [
                  'message' => 'Se eliminó de la base local pero no del checkpoint',
                  'status_code' => 20
               ]
            ]);
         }
      }else{
         return response()->json([
            'error' => [
               'message' => 'No se eliminó de la base de datos local',
               'status_code' => 20
            ]
         ]);
      }
   }

   public function editIps(Request $request){

		Log::info($request);
		//die();

      $id_ip = $request['id_ip'];
      $object_name = $request['object_name'];
      $old_ip_initial = $request['old_ip'];
      $old_ip_last = $request['old_ip'];

      $new_ip_initial = $request['new_ip'];
      $new_ip_last = $request['new_ip'];

      $old_range = $old_ip_initial.' '.$old_ip_initial;
      $new_range = $new_ip_initial.' '.$new_ip_last;

      $ip_object = LayerSecurity::find($id_ip);
		$ip_object->ip_initial = $new_ip_initial;
		$ip_object->ip_last = $new_ip_last;
		$ip_object->save();

      if($ip_object){
			Log::info('ip_object');

			$ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$old_ip_initial." ".$old_ip_last."'";
         \SSH::into('checkpoint')->run($ssh_command, function($line){
            Log::info($line.PHP_EOL);
            $this->output = $line.PHP_EOL;
         });

         sleep(2);

         $evaluate = $this->output;
         Log::info("1099 ". $evaluate);

         while ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
            Log::info("remove ip existe try again");
            \SSH::into('checkpoint')->run($ssh_command, function($line){
               Log::info($line.PHP_EOL);
               $this->output = $line.PHP_EOL;
            });

            $evaluate = $this->output;
         }

         sleep(2);

         $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$old_ip_initial." ".$old_ip_last."'";
         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            Log::info($line2.PHP_EOL);
            $this->output = $line2.PHP_EOL;
         });

         $evaluate = $this->output;
         Log::info("1120 ". $evaluate);

         sleep(2);

         while( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
            Log::info("2 remove ip existe try again");
            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            $evaluate = $this->output;
         }

         sleep(2);

			$ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$old_ip_initial." ".$old_ip_last."'";
         \SSH::into('checkpoint')->run($ssh_command3, function($line2){
            Log::info($line2.PHP_EOL);
            $this->output = $line2.PHP_EOL;
         });

         $evaluate = $this->output;
         Log::info("1120 ". $evaluate);

         sleep(2);

         while( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
            Log::info("2 remove ip existe try again");
            \SSH::into('checkpoint')->run($ssh_command3, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            $evaluate = $this->output;
         }

         sleep(2);

			$ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$old_ip_initial." ".$old_ip_last."'";
         \SSH::into('checkpoint')->run($ssh_command4, function($line2){
            Log::info($line2.PHP_EOL);
            $this->output = $line2.PHP_EOL;
         });

         $evaluate = $this->output;
         Log::info("1120 ". $evaluate);

         sleep(2);

         while( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
            Log::info("2 remove ip existe try again");
            \SSH::into('checkpoint')->run($ssh_command4, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            $evaluate = $this->output;
         }

         sleep(2);

         $object_list = DB::connection('checkpoint')->select('SELECT * FROM ip_object_list WHERE ip_initial="'.$old_ip_initial.'" AND ip_last="'.$old_ip_last.'"');
         $object_list = json_decode(json_encode($object_list), true);

         foreach($object_list as $row){
            $id_obj_list = $row['id'];
         }

         $bd_ips_obj = DB::connection('checkpoint')->update("UPDATE ip_object_list SET ip_initial='".$new_ip_initial."', ip_last='".$new_ip_last."' WHERE id=".$id_obj_list);

         if($bd_ips_obj){

				Log::info('bd_ips_obj');

				$ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";
				$ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";
				$ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";
				$ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$new_ip_initial." ".$new_ip_last."'";

            //Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command, function($line){
               Log::info($line.PHP_EOL);
               $this->output = $line.PHP_EOL;
            });

            sleep(2);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 112");
               \SSH::into('checkpoint')->run($ssh_command, function($line){
                  Log::info($line.PHP_EOL);
                  $this->output = $line.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(2);

            //Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            sleep(2);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 113");
               \SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  Log::info($line2.PHP_EOL);
                  $this->output = $line2.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(2);

				//Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command3, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            sleep(2);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 113");
               \SSH::into('checkpoint')->run($ssh_command3, function($line2){
                  Log::info($line2.PHP_EOL);
                  $this->output = $line2.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(2);

				//Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command4, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            sleep(2);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 113");
               \SSH::into('checkpoint')->run($ssh_command4, function($line2){
                  Log::info($line2.PHP_EOL);
                  $this->output = $line2.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(2);

            return response()->json([
					'success' => [
						'message' => "Datos actualizados correctamente",
						'status_code' => 200
					]
				]);
         }else{
				Log::info('NO bd_ips_obj');
            return response()->json([
               'error' => [
                  'message' => "Datos actualizados en base local pero no en checkpoint",
                  'status_code' => 20
               ]
            ]);
         }
      }else{
			Log::info('NO ip_object');
         return response()->json([
            'error' => [
               'message' => "No se pudo editar la lista",
               'status_code' => 20
            ]
         ]);
      }
   }

	public function removeIpCheckpointBlock(Request $request){
		Log::info($request);
		$object_name = $request['object_name'];
		$ip_initial = $request['ip_initial'];
		$ip_last = $request['ip_last'];
		$id = $request['id'];
		$evaluate = "";

		$total_ips = 1;
		$flag = 0;

      $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      \SSH::into('checkpoint')->run($ssh_command, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
			Log::info($line2.PHP_EOL);
			$evaluate = $line2.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
				Log::info($line2.PHP_EOL);
				$evaluate = $line2.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
			Log::info($line3.PHP_EOL);
			$evaluate = $line3.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
				Log::info($line3.PHP_EOL);
				$evaluate = $line3.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
			Log::info($line4.PHP_EOL);
			$evaluate = $line4.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
				Log::info($line4.PHP_EOL);
				$evaluate = $line4.PHP_EOL;
			});
		}

		sleep(2);

		$update_obj = LayerSecurity::where('id', $id)
			->update(['status' => "No está en checkpoint", 'status_num' => 0]);

		return response()->json([
			'success' => [
				'message' => 'IP se quitó de la lista de checkpoint-block',
				'status_code' => 200
			]
		]);
   }

	public function assignIpCheckpointBlock(Request $request){

		$object_name = $request['object_name'];
		$ip_initial = $request['ip_initial'];
		$ip_last = $request['ip_last'];
		$id = $request['id'];
		$evaluate = "";

		$total_ips = 1;
		$flag = 0;

      $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      \SSH::into('checkpoint')->run($ssh_command, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
			Log::info($line2.PHP_EOL);
			$evaluate = $line2.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
				Log::info($line2.PHP_EOL);
				$evaluate = $line2.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
			Log::info($line3.PHP_EOL);
			$evaluate = $line3.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
				Log::info($line3.PHP_EOL);
				$evaluate = $line3.PHP_EOL;
			});
		}

		sleep(2);

		$flag = 0;
		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
			Log::info($line4.PHP_EOL);
			$evaluate = $line4.PHP_EOL;
		});

		$evaluate = $this->output;

		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
			if($flag >= 3) break;
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
				Log::info($line4.PHP_EOL);
				$evaluate = $line4.PHP_EOL;
			});
		}

		sleep(2);

		$update_obj = LayerSecurity::where('id', $id)
			->update(['status' => "Esta en checkpoint", 'status_num' => 1]);

		return response()->json([
			'success' => [
				'message' => 'IP se agregó nuevamente a la lista de checkpoint-block',
				'status_code' => 200
			]
		]);
	}
}

/*usuario: control4
Contraseña: FA7bkN3tcciXXhb

Comando: tscpgw_api

IP: 172.16.3.9


REVISAR
orderObjectsBD CHECKPOINTCONTROLLER
