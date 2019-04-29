<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Datatables;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\LogsData;
use App\ThreatIps;
use App\LayerSecurity;

use JWTAuth;


class AutomaticData extends Controller{

   private $output = "";
   private $verification;
   private $verif_obj;
   private $range = [];
   private $testtt = [];

   public function __construct(){
      $evaluate = "";
      #$verification = 1;
  	}

   public function getData(){
      $ips = ThreatIps::where('status', '=', 0)->take(2)->get();

      foreach($ips as $row){
         $datos[] = array("ip" => $row['ip'], "object" => $row['object_name'], "id" => $row['_id']);

         $save = $this->sendDataCheckpoint($datos);
         Log::info($save);
      }
   }

   public function sendDataCheckpoint($datos){
      Log::info("LLEGA AL SEND");

      $validateCmd = new ValidateCommandController;
      $total_ips = 1;
      $current_ips = 1;

      foreach($datos as $val){
         Log::info($val);
         $ip = $val['ip'];
         $object_name = $val['object'];
         $id_obj = $val['id'];

         $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$ip." ".$ip."'";
         $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$ip." ".$ip."'";
         $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$ip." ".$ip."'";
         $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$ip." ".$ip."'";
         $flag = 0;
         $flag2 = 0;
         $array_data_err = [];
         $temp_data_err = [];
         $array_data_succ = [];
         $temp_data_succ = [];
         $condition;

         $verification = 1;

         $exist_object112 = $validateCmd->verifyExistObject('172.16.3.112', $object_name);
         Log::info("AUTO exist object 112 ".$exist_object112);
         if($exist_object112 == 1){

            \SSH::into('checkpoint')->run($ssh_command, function($line){
      			Log::info($line.PHP_EOL);
      			$evaluate = $line.PHP_EOL;
      		});

            $evaluate = $this->output;

            while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
               if($flag >= 2) break;
               $flag++;
      			Log::info("AUTO 1 existe try again 112");
      			\SSH::into('checkpoint')->run($ssh_command, function($line){
      				Log::info($line.PHP_EOL);
      				$evaluate = $line.PHP_EOL;
      			});
      		}

            /*Log::info("AUTO flag 112");
            Log::info($flag);
            Log::info($evaluate);*/

            sleep(3);
            //$ssh_commVer112 = "tscpgw_api -g '172.16.3.112' -a search -o ".$object_name." -r '".$ip." ".$ip."'";
            $exist_range = $validateCmd->existIpRange($object_name, $ip, $ip, '172.16.3.112');
            /*Log::info("AUTO exist range 112 ");
            Log::info($exist_range);*/
            if($exist_range['response'] == 0){
               $temp_data_err = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip");
               array_push($array_data_err, $temp_data_err);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.112";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 0;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*-----*/

            }else{
               $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
               array_push($array_data_succ, $temp_data_succ);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.112";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 1;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*------*/

               $update_threat = ThreatIps::where('_id', $id_obj)
      				->update(['status' => 1]);
            }

            /*Log::info("112");
            Log::info($array_data_err);
            Log::info($array_data_succ);*/

         }else{
            Log::info("AUTO No existe el objeto en el 112");
         }

   		sleep(2);

         $flag = 0;
         $flag2 = 0;

         $exist_object113 = $validateCmd->verifyExistObject('172.16.3.113', $object_name);
         Log::info("AUTO exist object 113 ".$exist_object113);
         if($exist_object113 == 1){

            \SSH::into('checkpoint')->run($ssh_command2, function($line2){
      			Log::info($line2.PHP_EOL);
      			$evaluate = $line2.PHP_EOL;
      		});

            $evaluate = $this->output;

      		while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
               if($flag >= 2) break;
               $flag++;
      			Log::info("AUTO 1 existe try again 113");
      			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
      				Log::info($line2.PHP_EOL);
      				$evaluate = $line2.PHP_EOL;
      			});
      		}

            sleep(3);

            /*Log::info("flag 113");
            Log::info($flag);
            Log::info($evaluate);*/

            //$ssh_commVer113 = "tscpgw_api -g '172.16.3.113' -a search -o ".$object_name." -r '".$ip." ".$ip."'";
            $exist_range = $validateCmd->existIpRange($object_name, $ip, $ip, '172.16.3.113');
            /*Log::info("AUTO exist range 113 ");
            Log::info($exist_range);*/
            if($exist_range['response'] == 0){
               $temp_data_err = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip");
               array_push($array_data_err, $temp_data_err);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.113";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 0;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*-----*/

            }else{
               $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
               array_push($array_data_succ, $temp_data_succ);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.113";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 1;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*-----*/

               $update_threat = ThreatIps::where('_id', $id_obj)
			        ->update(['status' => 1]);
            }

            /*Log::info("113");
            Log::info($array_data_err);
            Log::info($array_data_succ);*/

         }else{
            Log::info("AUTO No existe el objeto en el 113");
         }

         sleep(2);

         $flag = 0;
         $flag2 = 0;

         $exist_object116 = $validateCmd->verifyExistObject('172.16.3.116', $object_name);
         Log::info("AUTO exist object 116 ".$exist_object116);
         if($exist_object116 == 1){

            \SSH::into('checkpoint')->run($ssh_command3, function($line3){
      			Log::info($line3.PHP_EOL);
      			$evaluate = $line3.PHP_EOL;
      		});

            $evaluate = $this->output;

      		while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
               if($flag >= 2) break;
               $flag++;
      			Log::info("AUTO 1 existe try again 116");
      			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
      				Log::info($line3.PHP_EOL);
      				$evaluate = $line3.PHP_EOL;
      			});
      		}

            /*Log::info("flag 116");
            Log::info($flag);
            Log::info($evaluate);*/

            sleep(3);
            //$ssh_commVer116 = "tscpgw_api -g '172.16.3.116' -a search -o ".$object_name." -r '".$ip." ".$ip."'";
            $exist_range = $validateCmd->existIpRange($object_name, $ip, $ip, '172.16.3.116');
            /*Log::info("AUTO exist range 116 ");
            Log::info($exist_range);*/
            if($exist_range['response'] == 0){
               $temp_data_err = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip");
               array_push($array_data_err, $temp_data_err);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.116";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 0;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*-----*/

            }else{
               $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
               array_push($array_data_succ, $temp_data_succ);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.116";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 1;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*-----*/

               $update_threat = ThreatIps::where('_id', $id_obj)
      				->update(['status' => 1]);
            }

            /*Log::info("116");
            Log::info($array_data_err);
            Log::info($array_data_succ);*/


         }else{
            Log::info("AUTO No existe el objeto en el 116");
         }

         sleep(2);

         $flag = 0;
         $flag2 = 0;

         $exist_object117 = $validateCmd->verifyExistObject('172.16.3.117', $object_name);
         //Log::info("AUTO exist object 117 ".$exist_object117);
         if($exist_object117 == 1){

            \SSH::into('checkpoint')->run($ssh_command4, function($line4){
      			Log::info($line4.PHP_EOL);
      			$evaluate = $line4.PHP_EOL;
      		});

            $evaluate = $this->output;

      		while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
               if($flag >= 2) break;
               $flag++;
      			//Log::info("AUTO 1 existe try again 117");
      			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
      				Log::info($line4.PHP_EOL);
      				$evaluate = $line4.PHP_EOL;
      			});
      		}

            sleep(3);

            /*Log::info("flag 117");
            Log::info($flag);
            Log::info($evaluate);*/

            //$ssh_commVer117 = "tscpgw_api -g '172.16.3.117' -a search -o ".$object_name." -r '".$ip." ".$ip."'";
            $exist_range = $validateCmd->existIpRange($object_name, $ip, $ip, '172.16.3.117');
            /*Log::info("AUTO exist range 117 ");
            Log::info($exist_range);*/
            if($exist_range['response'] == 0){
               $temp_data_err = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip");
               array_push($array_data_err, $temp_data_err);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.117";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 0;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*----*/
            }else{
               $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip, "ip_last" => $ip, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
               array_push($array_data_succ, $temp_data_succ);

               //Guardaré en mongo los logs ya sean buenos o malos
               /*$log = new HistoricalData;
               $log->server = "172.16.3.117";
               $log->object_name = $object_name;
               $log->ip_initial = $ip;
               $log->ip_last = $ip;
               $log->type = "addrip";
               $log->class ="ip";
               $log->status = 1;
               $log->info = $exist_range['info'];
               $log->token_company = $exist_range['token'];
               $log->save();*----*/

               $update_threat = ThreatIps::where('_id', $id_obj)
      				->update(['status' => 1]);
            }

            sleep(2);

            /*Log::info("117");
            Log::info($array_data_err);
            Log::info($array_data_succ);*/
         }else{
            Log::info("AUTO No existe el objeto en el 117");
         }

         $arreglo = array("success" => $array_data_succ, "error" => $array_data_err, "info" => $total_ips);

         $count = LayerSecurity::where('ip_initial', '=', $ip)->count();

         if($count == 0){
            $list_sec = new LayerSecurity;
            $list_sec->name_object = $object_name;
            $list_sec->ip_initial = $ip;
            $list_sec->ip_last = $ip;
            $list_sec->comment = "automatic ip";
            $list_sec->server_id = 1;
            $list_sec->save();
         }else{
            Log::info("ya existe en la base");
         }

         return $arreglo;
      }
   }

}
