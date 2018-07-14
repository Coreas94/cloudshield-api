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

class LayersController extends Controller
{

	private $output;

   	public function __construct(){
		$evaluate = "";
	}

	public function getObjectByServers(Request $request){

      $data = getObjectServers($request['token']);
      return response()->json($data);
	}

	public function getIpsList(Request $request){

		//$list = LayerSecurity::join('fw_servers', 'fw_servers.id', '=', 'layers_security_list.server_id')->get();
		$list = DB::table('layers_security_list')
		    ->join('fw_servers', 'fw_servers.id', '=', 'layers_security_list.server_id')
		    ->select('layers_security_list.*', 'fw_servers.name AS name_server')
		    ->get();

		$list = json_decode(json_encode($list), true);

		return response()->json([
			'data' => $list
		]);
	}

	public function addIpList(Request $request, CheckpointController $checkpoint){

      $ip_initial = $request['ip_initial'];
      $ip_last = $ip_initial;
      $comment = $request['comment'];
      $name_object = $request['object_name'];
      $server_id = $request['server_id'];

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

         $bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $object_id_list, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(),
         'updated_at' => \Carbon\Carbon::now()]);

         Log::info($bd_ips_check);

         if($bd_ips_check){

            $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$name_object." -r ".$ip_initial." ".$ip_last;
            $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$name_object." -r ".$ip_initial." ".$ip_last;

            //Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command, function($line){
               Log::info($line.PHP_EOL);
               $this->output = $line.PHP_EOL;
            });

            sleep(3);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 112");
               \SSH::into('checkpoint')->run($ssh_command, function($line){
                  Log::info($line.PHP_EOL);
                  $this->output = $line.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(3);

            //Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            sleep(3);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 113");
               \SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  Log::info($line2.PHP_EOL);
                  $this->output = $line2.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(3);

            return response()->json([
               'success' => [
                  'message' => "Datos ingresados correctamente",
                  'status_code' => 200
               ]
            ]);
         }else{
            return response()->json([
               'error' => [
                  'message' => "No se guardó en el checkpoint, solo localmente!",
                  'status_code' => 20
               ]
            ]);
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "No se guardaron los datos",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function removeIpList(Request $request, CheckpointController $checkpoint){

      $object_name = $request['name_object'];
      $ip_initial = $request['ip_initial'];
      $ip_last = $ip_initial;
      $id_list = $request['id'];

      $ssh_command = 'tscpgw_api -g "172.16.3.112" -a delrip -o '.$object_name.' -r '. $ip_initial.' '.$ip_last;
      \SSH::into('checkpoint')->run($ssh_command, function($line){
         Log::info($line.PHP_EOL);
         $this->output = $line.PHP_EOL;
      });

      sleep(3);

      $evaluate = $this->output;
      Log::info("1099 ". $evaluate);

      while (stripos($evaluate, "try again") !== false) {
         Log::info("remove ip existe try again");
         \SSH::into('checkpoint')->run($ssh_command, function($line){
            Log::info($line.PHP_EOL);
            $this->output = $line.PHP_EOL;
         });

         $evaluate = $this->output;
      }

      sleep(3);

      $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a delrip -o '.$object_name.' -r '. $ip_initial.' '.$ip_last;
      \SSH::into('checkpoint')->run($ssh_command2, function($line2){
         Log::info($line2.PHP_EOL);
         $this->output = $line2.PHP_EOL;
      });

      $evaluate = $this->output;
      Log::info("1120 ". $evaluate);

      sleep(3);

      while (stripos($evaluate, "try again") !== false) {
         Log::info("2 remove ip existe try again");
         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            Log::info($line2.PHP_EOL);
            $this->output = $line2.PHP_EOL;
         });

         $evaluate = $this->output;
      }

      sleep(3);

      $delete_list = DB::table('layers_security_list')->where('id', '=', $id_list)->delete();

      if($delete_list){
         $object_list = DB::connection('checkpoint')->select('SELECT * FROM ip_object_list WHERE ip_initial="'.$ip_initial.'" AND ip_last="'.$ip_last.'"');
         $object_list = json_decode(json_encode($object_list), true);

         foreach($object_list as $row){
            $id_obj_list = $row['id'];
         }

         $delete_add_ch = DB::connection('checkpoint')->delete("DELETE FROM ip_object_list WHERE id=".$id_obj_list);

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

      $id_ip = $request['id_ip'];
      $object_name = $request['name_object'];
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

         $ssh_command = 'tscpgw_api -g "172.16.3.112" -a delrip -o '.$object_name.' -r '. $old_range;
         \SSH::into('checkpoint')->run($ssh_command, function($line){
            Log::info($line.PHP_EOL);
            $this->output = $line.PHP_EOL;
         });

         sleep(3);

         $evaluate = $this->output;
         Log::info("1099 ". $evaluate);

         while (stripos($evaluate, "try again") !== false) {
            Log::info("remove ip existe try again");
            \SSH::into('checkpoint')->run($ssh_command, function($line){
               Log::info($line.PHP_EOL);
               $this->output = $line.PHP_EOL;
            });

            $evaluate = $this->output;
         }

         sleep(3);

         $ssh_command2 = 'tscpgw_api -g "172.16.3.112" -a delrip -o '.$object_name.' -r '. $old_range;
         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            Log::info($line2.PHP_EOL);
            $this->output = $line2.PHP_EOL;
         });

         $evaluate = $this->output;
         Log::info("1120 ". $evaluate);

         sleep(3);

         while (stripos($evaluate, "try again") !== false) {
            Log::info("2 remove ip existe try again");
            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            $evaluate = $this->output;
         }

         sleep(3);

         $object_list = DB::connection('checkpoint')->select('SELECT * FROM ip_object_list WHERE ip_initial="'.$old_ip_initial.'" AND ip_last="'.$old_ip_last.'"');
         $object_list = json_decode(json_encode($object_list), true);

         foreach($object_list as $row){
            $id_obj_list = $row['id'];
         }

         $bd_ips_obj = DB::connection('checkpoint')->update("UPDATE ip_object_list SET ip_initial='".$new_ip_initial."', ip_last='".$new_ip_last."' WHERE id=".$id_obj_list);

         if($bd_ips_obj){

            $ssh_command = 'tscpgw_api -g "172.16.3.112" -a delrip -o '.$object_name.' -r '. $new_range;
            $ssh_command2 = 'tscpgw_api -g "172.16.3.112" -a delrip -o '.$object_name.' -r '. $new_range;

            //Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command, function($line){
               Log::info($line.PHP_EOL);
               $this->output = $line.PHP_EOL;
            });

            sleep(3);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 112");
               \SSH::into('checkpoint')->run($ssh_command, function($line){
                  Log::info($line.PHP_EOL);
                  $this->output = $line.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(3);

            //Ejecuto los comandos para crear los 2 rangos nuevos
            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
               Log::info($line2.PHP_EOL);
               $this->output = $line2.PHP_EOL;
            });

            sleep(3);

            $evaluate = $this->output;
            while(stripos($evaluate, "try again") !== false) {
               Log::info("existe try again layer 113");
               \SSH::into('checkpoint')->run($ssh_command2, function($line2){
                  Log::info($line2.PHP_EOL);
                  $this->output = $line2.PHP_EOL;
               });
               $evaluate = $this->output;
            }

            sleep(3);

            return response()->json([
					'success' => [
						'message' => "Datos actualizados correctamente",
						'status_code' => 200
					]
				]);

         }else{
            return response()->json([
               'error' => [
                  'message' => "Datos actualizados en base local pero no en checkpoint",
                  'status_code' => 20
               ]
            ]);
         }
      }else{
         return response()->json([
            'error' => [
               'message' => "No se pudo editar la lista",
               'status_code' => 20
            ]
         ]);
      }
   }

}
