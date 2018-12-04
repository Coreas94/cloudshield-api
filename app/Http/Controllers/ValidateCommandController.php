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

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;

class ValidateCommandController extends Controller{
   private $output = "";
   private $verification;
   private $verif_obj;
   private $prueba = [];

   public function __construct(){
      $evaluate = "";
      #$verification = 1;
  	}

   public function validateCreateObject($new_object_name){

      $ssh_command = 'tscpgw_api -g "172.16.3.112" -a adddyo -o '.$new_object_name;
      $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$new_object_name;
      $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a adddyo -o '.$new_object_name;
      $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a adddyo -o '.$new_object_name;
      $flag = 0;
      $temp_data = [];
      $array_data = [];

      \SSH::into('checkpoint')->run($ssh_command, function($line){
         Log::info($line.PHP_EOL);
         $evaluate = $line.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         $flag++;
         Log::info("1 existe try again 112");
         \SSH::into('checkpoint')->run($ssh_command, function($line){
            Log::info($line.PHP_EOL);
            $evaluate = $line.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data = array("server"=>"172.16.3.112", "object_name"=>$new_object_name, "type" => "adddyo", "class" => "object");
         array_push($array_data, $temp_data);
      }

      sleep(2);

      \SSH::into('checkpoint')->run($ssh_command2, function($line2){
         Log::info($line2.PHP_EOL);
         $evaluate = $line2.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         $flag++;
         Log::info("1 existe try again 113");
         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            Log::info($line2.PHP_EOL);
            $evaluate = $line2.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data = array("server"=>"172.16.3.113", "object_name"=>$new_object_name, "type" => "adddyo", "class" => "object");
         array_push($array_data, $temp_data);
      }

      sleep(2);

      \SSH::into('checkpoint')->run($ssh_command3, function($line3){
         Log::info($line3.PHP_EOL);
         $evaluate = $line3.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         $flag++;
         Log::info("1 existe try again 116");
         \SSH::into('checkpoint')->run($ssh_command3, function($line3){
            Log::info($line3.PHP_EOL);
            $evaluate = $line3.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data = array("server"=>"172.16.3.116", "object_name"=>$new_object_name, "type" => "adddyo", "class" => "object");
         array_push($array_data, $temp_data);
      }

      sleep(2);

      \SSH::into('checkpoint')->run($ssh_command4, function($line4){
         Log::info($line4.PHP_EOL);
         $evaluate = $line4.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         $flag++;
         Log::info("1 existe try again 117");
         \SSH::into('checkpoint')->run($ssh_command4, function($line4){
            Log::info($line4.PHP_EOL);
            $evaluate = $line4.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data = array("server"=>"172.16.3.117", "object_name"=>$new_object_name, "type" => "adddyo", "class" => "object");
         array_push($array_data, $temp_data);
      }

      Session::put('data_tmp', $array_data);

      return "success";
   }


   public function validateAssignIpObject($object_name, $ip_initial, $ip_last, $total_ips, $current_ips){

      $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $flag = 0;
      $flag2 = 0;
      $array_data_err = [];
      $temp_data_err = [];
      $array_data_succ = [];
      $temp_data_succ = [];
      $condition;

      $verification = 1;

      $exist_object112 = $this->verifyExistObject('172.16.3.112', $object_name);

      if($exist_object112 == 1){

         \SSH::into('checkpoint')->run($ssh_command, function($line){
   			Log::info($line.PHP_EOL);
   			$evaluate = $line.PHP_EOL;
   		});

         $evaluate = $this->output;

         while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
            if($flag >= 2) break;
            $flag++;
   			Log::info("1 existe try again 112");
   			\SSH::into('checkpoint')->run($ssh_command, function($line){
   				Log::info($line.PHP_EOL);
   				$evaluate = $line.PHP_EOL;
   			});
   		}

         Log::info("flag 112");
         Log::info($flag);
         Log::info($evaluate);

         sleep(3);
         $ssh_commVer112 = "tscpgw_api -g '172.16.3.112' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

         \SSH::into('checkpoint')->run($ssh_commVer112, function($line){
            Log::info("verification 112");
   			Log::info($line.PHP_EOL);
   			$this->verification = $line.PHP_EOL;
   		});

         sleep(1);

         while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
            if($flag2 >= 2) break;
            $flag2++;

            \SSH::into('checkpoint')->run($ssh_commVer112, function($line){
               Log::info("verification while 112");
               Log::info($line.PHP_EOL);
               $verification = $line.PHP_EOL;
            });
            Log::info($flag2);
   		}

         Log::info("flag 2 112");
         Log::info($flag2);
         Log::info($this->verification);

         if($flag >= 2 || $this->verification == 0 ){
            $temp_data_err = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
            array_push($array_data_err, $temp_data_err);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.112";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 0;
            $log->save();

         }else{
            $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
            array_push($array_data_succ, $temp_data_succ);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.112";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 1;
            $log->save();
         }

         Log::info("112");
         Log::info($array_data_err);
         Log::info($array_data_succ);

      }else{
         Log::info("No existe el objeto en el 112");
      }

		sleep(2);

      $flag = 0;
      $flag2 = 0;

      $exist_object113 = $this->verifyExistObject('172.16.3.113', $object_name);

      if($exist_object113 == 1){

         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
   			Log::info($line2.PHP_EOL);
   			$evaluate = $line2.PHP_EOL;
   		});

         $evaluate = $this->output;

   		while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
            if($flag >= 2) break;
            $flag++;
   			Log::info("1 existe try again 113");
   			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
   				Log::info($line2.PHP_EOL);
   				$evaluate = $line2.PHP_EOL;
   			});
   		}

         sleep(3);

         Log::info("flag 113");
         Log::info($flag);
         Log::info($evaluate);

         $ssh_commVer113 = "tscpgw_api -g '172.16.3.113' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

         \SSH::into('checkpoint')->run($ssh_commVer113, function($line){
            Log::info("verification 113");
   			Log::info($line.PHP_EOL);
   			$this->verification = $line.PHP_EOL;
   		});

         sleep(1);

         while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
            if($flag2 >= 2) break;

            $flag2++;

            \SSH::into('checkpoint')->run($ssh_commVer113, function($line){
               Log::info("verification while 113");
               Log::info($line.PHP_EOL);
               $this->verification = $line.PHP_EOL;
            });
            Log::info($flag2);
   		}

         Log::info("flag 2 113");
         Log::info($flag2);
         Log::info($this->verification);

         if($flag >= 2 || $this->verification == 0 ){
            $temp_data_err = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
            array_push($array_data_err, $temp_data_err);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.113";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 0;
            $log->save();

         }else{
            $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
            array_push($array_data_succ, $temp_data_succ);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.113";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 1;
            $log->save();
         }

         Log::info("113");
         Log::info($array_data_err);
         Log::info($array_data_succ);

      }else{
         Log::info("No existe el objeto en el 113");
      }

      sleep(2);

      $flag = 0;
      $flag2 = 0;

      $exist_object116 = $this->verifyExistObject('172.16.3.116', $object_name);

      if($exist_object116 == 1){

         \SSH::into('checkpoint')->run($ssh_command3, function($line3){
   			Log::info($line3.PHP_EOL);
   			$evaluate = $line3.PHP_EOL;
   		});

         $evaluate = $this->output;

   		while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
            if($flag >= 2) break;
            $flag++;
   			Log::info("1 existe try again 116");
   			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
   				Log::info($line3.PHP_EOL);
   				$evaluate = $line3.PHP_EOL;
   			});
   		}

         Log::info("flag 116");
         Log::info($flag);
         Log::info($evaluate);

         sleep(3);
         $ssh_commVer116 = "tscpgw_api -g '172.16.3.116' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

         \SSH::into('checkpoint')->run($ssh_commVer116, function($line){
            Log::info("verification 116");
   			Log::info($line.PHP_EOL);
   			$this->verification = $line.PHP_EOL;
   		});

         sleep(1);

         while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
            if($flag2 >= 2) break;
            $flag2++;

            \SSH::into('checkpoint')->run($ssh_commVer116, function($line){
               Log::info("verification while 116");
               Log::info($line.PHP_EOL);
               $this->verification = $line.PHP_EOL;
            });
            Log::info($flag2);
   		}

         Log::info("flag 2 116");
         Log::info($flag2);
         Log::info($this->verification);

         if($flag >= 2 || $this->verification == 0 ){
            $temp_data_err = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
            array_push($array_data_err, $temp_data_err);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.116";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 0;
            $log->save();

         }else{
            $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
            array_push($array_data_succ, $temp_data_succ);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.116";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 1;
            $log->save();
         }

         Log::info("116");
         Log::info($array_data_err);
         Log::info($array_data_succ);


      }else{
         Log::info("No existe el objeto en el 116");
      }

      sleep(2);

      $flag = 0;
      $flag2 = 0;

      $exist_object117 = $this->verifyExistObject('172.16.3.117', $object_name);

      if($exist_object117 == 1){

         \SSH::into('checkpoint')->run($ssh_command4, function($line4){
   			Log::info($line4.PHP_EOL);
   			$evaluate = $line4.PHP_EOL;
   		});

         $evaluate = $this->output;

   		while ( ((stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag >= 2)) {
            if($flag >= 2) break;
            $flag++;
   			Log::info("1 existe try again 117");
   			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
   				Log::info($line4.PHP_EOL);
   				$evaluate = $line4.PHP_EOL;
   			});
   		}

         sleep(3);

         Log::info("flag 117");
         Log::info($flag);
         Log::info($evaluate);

         $ssh_commVer117 = "tscpgw_api -g '172.16.3.117' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

         \SSH::into('checkpoint')->run($ssh_commVer117, function($line){
            Log::info("verification 117");
   			Log::info($line.PHP_EOL);
   			$this->verification = $line.PHP_EOL;
   		});

         sleep(1);

         while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
            if($flag2 >= 2) break;
            $flag2++;

            \SSH::into('checkpoint')->run($ssh_commVer117, function($line){
               Log::info("verification while 117");
               Log::info($line.PHP_EOL);
               $this->verification = $line.PHP_EOL;
            });
            Log::info($flag2);
   		}

         Log::info("flag 2 117");
         Log::info($flag2);
         Log::info($this->verification);

         if($flag >= 2 || $this->verification == 0 ){
            $temp_data_err = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
            array_push($array_data_err, $temp_data_err);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.117";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 0;
            $log->save();
         }else{
            $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
            array_push($array_data_succ, $temp_data_succ);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = "172.16.3.117";
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 1;
            $log->save();
         }

         sleep(2);

         Log::info("117");
         Log::info($array_data_err);
         Log::info($array_data_succ);


      }else{
         Log::info("No existe el objeto en el 117");
      }

      $arreglo = array("success" => $array_data_succ, "error" => $array_data_err, "info" => $total_ips);

      return $arreglo;
   }

   public function resendDataTemp($token){

      $userLog = JWTAuth::toUser($token);
      //Log::info($userLog);
      $api_token = $userLog['api_token'];
      $company_id = $userLog['company_id'];
      $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
      $company_data2 = json_decode(json_encode($company_data), true);

      $name_company = $company_data2[0]['name'];

      //EVALUAR ARCHIVO JSON
      $path = storage_path() ."/app/".$name_company."/".$api_token.".json";
      $data_exist = json_decode(file_get_contents($path), true);
      //Log::info($data_exist);
      $temp_error = [];
      $flag = 0;
      $flag2 = 0;

      $condition;
      $verification = 1;
      Log::info("el data exist es: ");
      Log::info($data_exist);
      foreach ($data_exist as $value) {
         foreach ($value as $key => $row) {
            if($key == "error" && !empty($row)){
               array_push($temp_error, $row);
            }
         }
      }

      $evaluate = "";

      if(!empty($temp_error[0])){
         foreach ($temp_error[0] as $key => $value) {

            if($value['class'] == "object"){
               $ssh_command = 'tscpgw_api -g '.$value['server'].' -a '.$value['type'].' -o '.$value['object_name'];
               Log::info($ssh_command);

               \SSH::into('checkpoint')->run($ssh_command, function($line){
                  Log::info($line.PHP_EOL);
                  $evaluate = $line.PHP_EOL;
               });

               $evaluate = $this->output;

               while ((stripos($evaluate, "try again") !== false)) {
                  Log::info("1 existe try again 112");
                  \SSH::into('checkpoint')->run($ssh_command, function($line){
                     Log::info($line.PHP_EOL);
                     $evaluate = $line.PHP_EOL;
                  });
               }

               sleep(2);

            }else{
               $ssh_command = "tscpgw_api -g '".$value['server']."' -a '".$value['type']."' -o ".$value['object_name']." -r '".$value['ip_initial']." ".$value['ip_last']."'";
               Log::info($ssh_command);

               /*\SSH::into('checkpoint')->run($ssh_command, function($line){
                  Log::info($line.PHP_EOL);
                  $evaluate = $line.PHP_EOL;
               });

               $evaluate = $this->output;

               while ((stripos($evaluate, "try again") !== false)) {
                  Log::info("1 existe try again 112");
                  \SSH::into('checkpoint')->run($ssh_command, function($line){
                     Log::info($line.PHP_EOL);
                     $evaluate = $line.PHP_EOL;
                  });
               }*/

               $flag = 0;
               $flag2 = 0;
               \SSH::into('checkpoint')->run($ssh_command, function($line4){
                  Log::info($line4.PHP_EOL);
                  $evaluate = $line4.PHP_EOL;
               });

               $evaluate = $this->output;

               while ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
                  if($flag >= 3) break;
                  $flag++;
                  Log::info("1 existe try again 117");
                  \SSH::into('checkpoint')->run($ssh_command, function($line4){
                  	Log::info($line4.PHP_EOL);
                  	$evaluate = $line4.PHP_EOL;
                  });
               }

               sleep(3);

               Log::info("flag 117");
               Log::info($flag);
               Log::info($evaluate);

               $ssh_commVer = "tscpgw_api -g '".$value['server']."' -a search -o ".$value['object_name']." -r '".$value['ip_initial']." ".$value['ip_last']."'";

               \SSH::into('checkpoint')->run($ssh_commVer, function($line){
               	Log::info("verification server 117");
                  Log::info($line.PHP_EOL);
                  $this->verification = $line.PHP_EOL;
               });

               sleep(1);

               while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) ) {
                  if($flag2 >= 3) break;
                  $flag2++;

                  \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                     Log::info("verification while 117");
                     Log::info($line.PHP_EOL);
                     $this->verification = $line.PHP_EOL;
               	});
               	Log::info($flag2);
               }

               if($this->verification == 1 ){
                  $arreglo = array("success" => "", "error" => "", "info" => 0);

                  $json_response = json_encode($arreglo);
                  \Storage::put($name_company.'/'.$api_token.'.json', $json_response);
               }else{
                  Log::info("la verifiacion no es 1");
                  Log::info("No se han podido quitar los errores");
                  return "No se han podido quitar los errores";
               }
            }
         }

      }else{
         Log::info("No hay nada en error");
         $arreglo = array("success" => "", "error" => "", "info" => 0);

         $json_response = json_encode($arreglo);
         \Storage::put($name_company.'/'.$api_token.'.json', $json_response);
         return "No hay errores";
      }
   }

   public function validateRemoveObject($object_name){

      $ssh_command = 'tscpgw_api -g "172.16.3.112" -a deldyo -o '.$object_name;
      $ssh_command2 = 'tscpgw_api -g "172.16.3.113" -a deldyo -o '.$object_name;
      $ssh_command3 = 'tscpgw_api -g "172.16.3.116" -a deldyo -o '.$object_name;
      $ssh_command4 = 'tscpgw_api -g "172.16.3.117" -a deldyo -o '.$object_name;
      $flag = 0;
      $flag2 = 0;
      $array_data_err = [];
      $temp_data_err = [];
      $array_data_succ = [];
      $temp_data_succ = [];

      \SSH::into('checkpoint')->run($ssh_command, function($line){
         Log::info($line.PHP_EOL);
         $evaluate = $line.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         Log::info("1 existe try again 112");
         \SSH::into('checkpoint')->run($ssh_command, function($line){
            Log::info($line.PHP_EOL);
            $evaluate = $line.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.112", "object_name"=>$new_object_name, "type" => "deldyo", "class" => "object");
         array_push($array_data_err, $temp_data_err);
      }

      sleep(2);

      \SSH::into('checkpoint')->run($ssh_command2, function($line2){
         Log::info($line2.PHP_EOL);
         $evaluate = $line2.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         Log::info("1 existe try again 113");
         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            Log::info($line2.PHP_EOL);
            $evaluate = $line2.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.113", "object_name"=>$new_object_name, "type" => "deldyo", "class" => "object");
         array_push($array_data_err, $temp_data_err);
      }

      sleep(2);

      \SSH::into('checkpoint')->run($ssh_command3, function($line3){
         Log::info($line3.PHP_EOL);
         $evaluate = $line3.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         Log::info("1 existe try again 116");
         \SSH::into('checkpoint')->run($ssh_command3, function($line3){
            Log::info($line3.PHP_EOL);
            $evaluate = $line3.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.116", "object_name"=>$new_object_name, "type" => "deldyo", "class" => "object");
         array_push($array_data_err, $temp_data_err);
      }

      sleep(2);

      \SSH::into('checkpoint')->run($ssh_command4, function($line4){
         Log::info($line4.PHP_EOL);
         $evaluate = $line4.PHP_EOL;
      });

      $evaluate = $this->output;

      while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
         Log::info("1 existe try again 117");
         \SSH::into('checkpoint')->run($ssh_command4, function($line4){
            Log::info($line4.PHP_EOL);
            $evaluate = $line4.PHP_EOL;
         });
      }

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.117", "object_name"=>$new_object_name, "type" => "deldyo", "class" => "object");
         array_push($array_data_err, $temp_data_err);
      }

      sleep(2);

      Session::put('temp_data_err', $array_data_err);

      return "success";
   }

   public function validateRemoveIpObject($object_name, $ip_initial, $ip_last, $total_ips, $current_ips){
      Log::info($object_name.' '.$ip_initial.' '.$ip_last.' '.$total_ips.' '.$current_ips);
      //die();

      $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      $flag = 0;
      $array_data_err = [];
      $temp_data_err = [];
      $array_data_succ = [];
      $temp_data_succ = [];

      $flag2 = 0;
      $condition;

		\SSH::into('checkpoint')->run($ssh_command, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag >= 2)) {
         if($flag >= 2) break;
         $flag++;
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

      Log::info("flag 112");
      Log::info($flag);
      Log::info($evaluate);

      sleep(3);
      $ssh_commVer112 = "tscpgw_api -g '172.16.3.112' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      \SSH::into('checkpoint')->run($ssh_commVer112, function($line){
         Log::info("verification 112");
      	Log::info($line.PHP_EOL);
      	$this->verification = $line.PHP_EOL;
      });

      sleep(1);

      while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "1") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
         if($flag2 >= 2) break;
         $flag2++;

         \SSH::into('checkpoint')->run($ssh_commVer112, function($line){
            Log::info("verification while 112");
            Log::info($line.PHP_EOL);
            $verification = $line.PHP_EOL;
         });
         Log::info($flag2);
      }

      if($flag >= 2 || $this->verification == 1 ){
         $temp_data_err = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 0;
         $log->save();

      }else{
         $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 1;
         $log->save();
      }

		sleep(2);

      /****************************************************/

      $flag = 0;
      $flag2 = 0;

      \SSH::into('checkpoint')->run($ssh_command2, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag >= 2)) {
         if($flag >= 2) break;
         $flag++;
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

      Log::info("flag 113");
      Log::info($flag);
      Log::info($evaluate);

      sleep(3);
      $ssh_commVer113 = "tscpgw_api -g '172.16.3.113' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      \SSH::into('checkpoint')->run($ssh_commVer112, function($line){
         Log::info("verification 112");
      	Log::info($line.PHP_EOL);
      	$this->verification = $line.PHP_EOL;
      });

      sleep(1);

      while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "1") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
         if($flag2 >= 2) break;
         $flag2++;

         \SSH::into('checkpoint')->run($ssh_commVer113, function($line){
            Log::info("verification while 113");
            Log::info($line.PHP_EOL);
            $verification = $line.PHP_EOL;
         });
         Log::info($flag2);
      }

      if($flag >= 2 || $this->verification == 1 ){
         $temp_data_err = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 0;
         $log->save();

      }else{
         $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 1;
         $log->save();

      }

      sleep(2);

      /***********************************************************/
      $flag = 0;
      $flag2 = 0;

      \SSH::into('checkpoint')->run($ssh_command3, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag >= 2)) {
         if($flag >= 2) break;
         $flag++;
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

      Log::info("flag 116");
      Log::info($flag);
      Log::info($evaluate);

      sleep(3);
      $ssh_commVer116 = "tscpgw_api -g '172.16.3.116' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      \SSH::into('checkpoint')->run($ssh_commVer116, function($line){
         Log::info("verification 116");
      	Log::info($line.PHP_EOL);
      	$this->verification = $line.PHP_EOL;
      });

      sleep(1);

      while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "1") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
         if($flag2 >= 2) break;
         $flag2++;

         \SSH::into('checkpoint')->run($ssh_commVer116, function($line){
            Log::info("verification while 116");
            Log::info($line.PHP_EOL);
            $verification = $line.PHP_EOL;
         });
         Log::info($flag2);
      }

      if($flag >= 2 || $this->verification == 1 ){
         $temp_data_err = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 0;
         $log->save();

      }else{
         $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 1;
         $log->save();
      }

      sleep(2);

      /*******************************************************/

      $flag = 0;
      $flag2 = 0;

      \SSH::into('checkpoint')->run($ssh_command4, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag >= 2)) {
         if($flag >= 2) break;
         $flag++;
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

      Log::info("flag 117");
      Log::info($flag);
      Log::info($evaluate);

      sleep(3);
      $ssh_commVer117 = "tscpgw_api -g '172.16.3.117' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

      \SSH::into('checkpoint')->run($ssh_commVer117, function($line){
         Log::info("verification 117");
      	Log::info($line.PHP_EOL);
      	$this->verification = $line.PHP_EOL;
      });

      sleep(1);

      while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "1") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
         if($flag2 >= 2) break;
         $flag2++;

         \SSH::into('checkpoint')->run($ssh_commVer117, function($line){
            Log::info("verification while 117");
            Log::info($line.PHP_EOL);
            $verification = $line.PHP_EOL;
         });
         Log::info($flag2);
      }

      if($flag >= 2 || $this->verification == 1 ){
         $temp_data_err = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 0;
         $log->save();

      }else{
         $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);

         //Guardaré en mongo los logs ya sean buenos o malos
         $log = new HistoricalData;
         $log->server = "172.16.3.112";
         $log->object_name = $object_name;
         $log->ip_initial = $ip_initial;
         $log->ip_last = $ip_last;
         $log->type = "delrip";
         $log->class ="ip";
         $log->status = 1;
         $log->save();

      }

      sleep(2);

      $arreglo = array("success" => $array_data_succ, "error" => $array_data_err, "info" => $total_ips);

      return $arreglo;
   }


   public function getErrorData(){

      $temp_error = [];
      $flag = 0;
      $flag2 = 0;

      $condition;
      $verification = 1;

      $datos = HistoricalData::where('status', '=', 0)->get();
      $countData = count($datos);

      if($countData > 0){
         $data = json_decode($datos, true);
         Log::info("aqui imprimo el HistoricalData");

         foreach($data as $row){

            if($row['class'] == 'ip'){

               $ssh_command = "tscpgw_api -g '".$row['server']."' -a '".$row['type']."' -o ".$row['object_name']." -r '".$row['ip_initial']." ".$row['ip_last']."'";
               $ssh_command2 = "tscpgw_api -g '".$row['server']."' -a searchobj -o ".$row['object_name'];
               Log::info($ssh_command);

               $flag = 0;
               $flag2 = 0;
               \SSH::into('checkpoint')->run($ssh_command, function($line4){
                  Log::info($line4.PHP_EOL);
                  $this->output = $line4.PHP_EOL;
               });

               $evaluate = $this->output;

               sleep(2);

               while ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
                  if($flag >= 2) break;
                  $flag++;
                  Log::info("1 existe try again resend");
                  \SSH::into('checkpoint')->run($ssh_command, function($line4){
                     Log::info($line4.PHP_EOL);
                     $evaluate = $line4.PHP_EOL;
                  });
               }

               sleep(3);

               Log::info("flag resend");
               Log::info($flag);
               Log::info($evaluate);

               $ssh_commVer = "tscpgw_api -g '".$row['server']."' -a search -o ".$row['object_name']." -r '".$row['ip_initial']." ".$row['ip_last']."'";
               Log::info($ssh_commVer);
               \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                  Log::info("verification server 117");
                  Log::info($line.PHP_EOL);
                  $this->verification = $line.PHP_EOL;
               });

               sleep(1);

               while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) ) {
                  if($flag2 >= 3) break;
                  $flag2++;

                  \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                     Log::info("verification while 117");
                     Log::info($line.PHP_EOL);
                     $this->verification = $line.PHP_EOL;
                  });
                  Log::info($flag2);
               }

               if($this->verification == 1 ){
                  //Se debe dejar
                  $update = HistoricalData::where('_id', '=', $row['_id'])->update(['status' => 1]);
               }else{
                  Log::info("ES CERO Y NO ACTUALIZA");
               }
            }else{

               /*$ssh_command = 'tscpgw_api -g '.$value['server'].' -a '.$value['type'].' -o '.$value['object_name'];
               Log::info($ssh_command);

               \SSH::into('checkpoint')->run($ssh_command, function($line){
                  Log::info($line.PHP_EOL);
                  $evaluate = $line.PHP_EOL;
               });

               $evaluate = $this->output;

               while ((stripos($evaluate, "try again") !== false)) {
                  Log::info("1 existe try again 112");
                  \SSH::into('checkpoint')->run($ssh_command, function($line){
                     Log::info($line.PHP_EOL);
                     $evaluate = $line.PHP_EOL;
                  });
               }

               sleep(2);*/
            }
         }
      }else{
         Log::info("No hay datos");
      }
   }

   public function evaluateErrors(){

      $datos = HistoricalData::where('status', '=', 0)->get();
      $countData = count($datos);

      if($countData > 0){

         return response()->json([
            'data' => $datos
         ]);
      }else{
         return response()->json([
            'data' => "No Data"
         ]);
      }
   }

   public function matchData(){

      $exist_mongo = HistoricalData::where('status', '=', 0)->get();
      $count_mongo = count($exist_mongo);
      $array_ip_mongo = [];
      $array_ip_mysql = [];
      $flag = 0;
      $flag2 = 0;

      $condition;
      $verification = 1;

      $exist_mysql = AddressObject::where('type_address_id', '=', 7)->get();

      if($count_mongo > 0){
         //Aqui evaluaré si esos rangos existen en la base de datos mysql
         foreach($exist_mongo as $row => $val){

            foreach($exist_mysql as $key => $value) {
               $range = Range::parse($value['ip_initial'].'-'.$value['ip_last']);
               foreach($range as $ip){
               	array_push($array_ip_mysql, (string)$ip);
               }
            }

            $range = Range::parse($val['ip_initial'].'-'.$val['ip_last']);
            foreach($range as $ip) {
            	//array_push($array_ip_mongo, (string)$ip);
               if(in_array((string)$ip, $array_ip_mysql)) {
                  Log::info("Si existe ".(string)$ip);

                  $ssh_commVer = "tscpgw_api -g '".$val['server']."' -a search -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

                  $exist_object = $this->verifyExistObject($val['server'], $val['object_name']);
                  sleep(1);

                  if($exist_object == 0){//El objeto no existe y hay que crearlo
                     Log::info("NO EXISTEEEEE");
                     $ssh_command_obj = "tscpgw_api -g '".$val['server']."' -a adddyo -o ".$val['object_name'];

                     \SSH::into('checkpoint')->run($ssh_command_obj, function($line3){
                        Log::info($line3.PHP_EOL);
                        $this->output = $line3.PHP_EOL;
                     });

                     $evaluate = $this->output;
                     $flag = 0;

                     while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
                        $flag++;
                        Log::info("1 existe try again 112");
                        \SSH::into('checkpoint')->run($ssh_command_obj, function($line4){
                           Log::info($line4.PHP_EOL);
                           $evaluate = $line4.PHP_EOL;
                        });
                     }

                     \SSH::into('checkpoint')->run($ssh_searchobj, function($line2){
                        Log::info("verification exist object");
                        Log::info($line2.PHP_EOL);
                        $this->verif_obj = $line2.PHP_EOL;
                     });

                     sleep(2);

                     if($this->verif_obj == 1){

                        $ssh_command = "tscpgw_api -g '".$val['server']."' -a '".$val['type']."' -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

                        $flag = 0;
                        $flag2 = 0;
                        \SSH::into('checkpoint')->run($ssh_command, function($line4){
                           Log::info($line4.PHP_EOL);
                           $this->output = $line4.PHP_EOL;
                        });

                        $evaluate = $this->output;

                        sleep(2);

                        while ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
                           if($flag >= 2) break;
                           $flag++;
                           Log::info("1 existe try again 117");
                           \SSH::into('checkpoint')->run($ssh_command, function($line4){
                              Log::info($line4.PHP_EOL);
                              $evaluate = $line4.PHP_EOL;
                           });
                        }

                        sleep(3);

                        Log::info("flag 117");
                        Log::info($flag);
                        Log::info($evaluate);

                        $ssh_commVer = "tscpgw_api -g '".$val['server']."' -a search -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

                        \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                           Log::info("verification server 117");
                           Log::info($line.PHP_EOL);
                           $this->verification = $line.PHP_EOL;
                        });

                        sleep(1);

                        while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) ) {
                           if($flag2 >= 3) break;
                           $flag2++;

                           \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                              Log::info("verification while 117");
                              Log::info($line.PHP_EOL);
                              $this->verification = $line.PHP_EOL;
                           });
                           Log::info($flag2);
                        }

                        if($this->verification == 1 ){
                           //Se debe dejar
                           $update = HistoricalData::where('_id', '=', $val['_id'])->update(['status' => 1]);
                        }else{
                           Log::info("ES CERO Y NO ACTUALIZA");
                        }
                     }else{
                        return "No se pudo crear el objeto";
                     }
                  }else{//Quiere decir que si existe el objeto
                     Log::info("SI EXISTEEEEE");
                     \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                        Log::info("verification server 117");
                        Log::info($line.PHP_EOL);
                        $this->verification = $line.PHP_EOL;
                     });

                     Log::info("*********");
                     Log::info($this->verification);

                     sleep(1);

                     if($this->verification == 0 ){
                        //Se debe dejar
                        $ssh_command = "tscpgw_api -g '".$val['server']."' -a '".$val['type']."' -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

                        $flag = 0;
                        $flag2 = 0;
                        \SSH::into('checkpoint')->run($ssh_command, function($line4){
                           Log::info($line4.PHP_EOL);
                           $this->output = $line4.PHP_EOL;
                        });

                        $evaluate = $this->output;

                        sleep(2);

                        while ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) {
                           if($flag >= 2) break;
                           $flag++;
                           Log::info("1 existe try again 117");
                           \SSH::into('checkpoint')->run($ssh_command, function($line4){
                              Log::info($line4.PHP_EOL);
                              $evaluate = $line4.PHP_EOL;
                           });
                        }

                        sleep(3);

                        Log::info("flag 117");
                        Log::info($flag);
                        Log::info($evaluate);

                        $ssh_commVer = "tscpgw_api -g '".$val['server']."' -a search -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

                        \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                           Log::info("verification server 117");
                           Log::info($line.PHP_EOL);
                           $this->verification = $line.PHP_EOL;
                        });

                        sleep(1);

                        while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) ) {
                           if($flag2 >= 3) break;
                           $flag2++;

                           \SSH::into('checkpoint')->run($ssh_commVer, function($line){
                              Log::info("verification while 117");
                              Log::info($line.PHP_EOL);
                              $this->verification = $line.PHP_EOL;
                           });
                           Log::info($flag2);
                        }

                        if($this->verification == 1 ){
                           //Se debe dejar
                           $update = HistoricalData::where('_id', '=', $val['_id'])->update(['status' => 1]);
                        }else{
                           Log::info("ES CERO Y NO ACTUALIZA");
                        }

                     }else{
                        Log::info("ES 1 Y YA EXISTE");
                     }
                  }
               }else{
                  Log::info("No existe ".(string)$ip);
                  $update = HistoricalData::where('_id', '=', $val['_id'])->update(['status' => 1]);
               }
            }
         }

      }else{
         return "No hay datos nuevos en mongo";
      }
   }

   public function verifyExistObject($server, $object_name){

      $ssh_command2 = "tscpgw_api -g '".$server."' -a searchobj -o ".$object_name;

      \SSH::into('checkpoint')->run($ssh_command2, function($line2){
      	Log::info("verification exist object");
      	Log::info($line2.PHP_EOL);
      	$this->verif_obj = $line2.PHP_EOL;
      });

      Log::info("respuesta verification ".$this->verif_obj);

      if($this->verif_obj == 0){//El objeto no existe y hay que crearlo
         Log::info("NO EXISTEEEEE");
         $ssh_command_obj = "tscpgw_api -g '".$server."' -a adddyo -o ".$object_name;

         \SSH::into('checkpoint')->run($ssh_command_obj, function($line3){
            Log::info($line3.PHP_EOL);
            $this->output = $line3.PHP_EOL;
         });

         $evaluate = $this->output;
         $flag = 0;
         while ((stripos($evaluate, "try again")) !== false || ($flag > 2)) {
            $flag++;
            Log::info("1 existe try again 112");
            \SSH::into('checkpoint')->run($ssh_command_obj, function($line4){
              Log::info($line4.PHP_EOL);
              $evaluate = $line4.PHP_EOL;
            });
         }

         $ssh_command2 = "tscpgw_api -g '".$server."' -a searchobj -o ".$object_name;

         \SSH::into('checkpoint')->run($ssh_command2, function($line2){
            Log::info("verification exist object");
            Log::info($line2.PHP_EOL);
            $this->verif_obj = $line2.PHP_EOL;
         });

         sleep(2);

         if($this->verif_obj == 0){
            return 0;
         }else{
            return 1;
         }
      }else{
         return 1;
      }
   }

   public function existIpRange(){

      $ip = '110.110.1.1';
      $object = "Object29Nov";
      $test = [];

      $command = "tscpgw_api -g 172.16.3.117 -a ranges -o ".$object;

      \SSH::into('checkpoint')->run($command, function($line2){
         Log::info($line2.PHP_EOL);
         $this->prueba = $line2.PHP_EOL;
      });

      return $this->prueba;

      //Log::info($test);

      //Log::info($this->prueba);
      // foreach($this->prueba as $row){
      //    Log::info($row);
      // }

   }

}
