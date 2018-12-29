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
   private $range = [];
   private $testtt = [];

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
         //$ssh_commVer112 = "tscpgw_api -g '172.16.3.112' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $exist_range = $this->existIpRange($object_name, $ip_initial, $ip_last, '172.16.3.112');

         /*\SSH::into('checkpoint')->run($ssh_commVer112, function($line){
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
         Log::info($this->verification);*/

         //if($flag >= 2 || $this->verification == 0 ){
         if($exist_range['response'] == 0){
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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

         //$ssh_commVer113 = "tscpgw_api -g '172.16.3.113' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $exist_range = $this->existIpRange($object_name, $ip_initial, $ip_last, '172.16.3.113');

         /*\SSH::into('checkpoint')->run($ssh_commVer113, function($line){
            Log::info("verification 113");
   			Log::info($line.PHP_EOL);
   			$this->verification = $line.PHP_EOL;
   		});

         sleep(1);

         while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) || ($flag2 >= 2)) {
            if($flag2 >= 2) break;

            $flag2++;

            \SSH::into('checkpoint')->run($ssh_commVer113, function($liremoveRangene){
               Log::info("verification while 113");
               Log::info($line.PHP_EOL);
               $this->verification = $line.PHP_EOL;
            });
            Log::info($flag2);
   		}

         Log::info("flag 2 113");
         Log::info($flag2);
         Log::info($this->verification);

         if($flag >= 2 || $this->verification == 0 ){*/
         if($exist_range['response'] == 0){
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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
         //$ssh_commVer116 = "tscpgw_api -g '172.16.3.116' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $exist_range = $this->existIpRange($object_name, $ip_initial, $ip_last, '172.16.3.116');

         /*\SSH::into('checkpoint')->run($ssh_commVer116, function($line){
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

         if($flag >= 2 || $this->verification == 0 ){*/
         if($exist_range['response'] == 0){
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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

         //$ssh_commVer117 = "tscpgw_api -g '172.16.3.117' -a search -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $exist_range = $this->existIpRange($object_name, $ip_initial, $ip_last, '172.16.3.117');

         /*\SSH::into('checkpoint')->run($ssh_commVer117, function($line){
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

         if($flag >= 2 || $this->verification == 0 ){*/
         if(['response'] == 0){
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
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
      $token_company = $company_data2[0]['token_company'];

      //EVALUAR ARCHIVO JSON
      $path = storage_path() ."/app/".$name_company."/".$token_company.".json";
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

               $ssh_commVer = "tscpgw_api -g '".$value['server']."' -a search -o ".$value['object_name']." -r '".$value['ip_initial']." ".$value['ip_last']."'";

               \SSH::into('checkpoint')->run($ssh_commVer, function($line){
               	Log::info("verification server 117");
                  Log::info($line.PHP_EOL);
                  $this->verification = $line.PHP_EOL;
               });

               sleep(1);

               while ((stripos($this->verification, "") !== false) || (stripos($this->verification, "0") !== false) || (stripos($this->verification, "try again") !== false) ) {
                  if($flag2 >= 2) break;
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
                  \Storage::put($name_company.'/'.$token_company.'.json', $json_response);
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
         \Storage::put($name_company.'/'.$token_company.'.json', $json_response);
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

      $array_success = [];
      $array_error = [];
      $array_container = [];

      //die();
      $servers = array('172.16.3.112', '172.16.3.113', '172.16.3.116', '172.16.3.117');

      //ME QUEDO AQUI PARA VERIFICAR LA RESPUESTA DEL CHECKPOINT Y ELIMINAR RANGOS
      foreach($servers as $row){
         Log::info("ROWWW ".$row);
         $cmd_exists = $this->evaluateRemoveIp($object_name, $ip_initial, $ip_last, $row);

         Log::info("cmd exist ".$row);
         Log::info($cmd_exists);

         if($cmd_exists['remove']['response'] == 0){//Significa que el rango se eliminó y debo crear los otros

            $count = count($cmd_exists['new_range']);

            if($count > 0){
               foreach($cmd_exists['new_range'] as $value){
                  $addNewRange = $this->agreggateNewRange($row, $object_name, $value, $value);

                  Log::info("addNewRange ".$row);
                  Log::info($addNewRange);

                  array_push($array_container, $addNewRange);
               }
            }else{

            }
         }else{
            Log::info("El remove no es CERO y no se eliminó el rango");
         }
      }

      sleep(2);
      Log::info($array_container);

      //$arreglo = array("success" => $array_data_succ, "error" => $array_data_err, "info" => $total_ips);

      return $array_container;
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

               /*$ssh_commVer = "tscpgw_api -g '".$row['server']."' -a search -o ".$row['object_name']." -r '".$row['ip_initial']." ".$row['ip_last']."'";
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
               }*/

               $exist_range = $this->existIpRange($row['object_name'], $row['ip_initial'], $row['ip_last'], $row['server']);

               if($exist_range['response'] == 1 ){
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

                        /*$ssh_commVer = "tscpgw_api -g '".$val['server']."' -a search -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

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
                        }*/
                        $exist_range = $this->existIpRange($val['object_name'], $val['ip_initial'], $val['ip_last'], $val['server']);

                        if($exist_range['response'] == 1 ){
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

                        /*$ssh_commVer = "tscpgw_api -g '".$val['server']."' -a search -o ".$val['object_name']." -r '".$val['ip_initial']." ".$val['ip_last']."'";

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
                        }*/

                        $exist_range = $this->existIpRange($val['object_name'], $val['ip_initial'], $val['ip_last'], $val['server']);

                        if($exist_range['response'] == 1 ){
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

   public function existIpRange($object_name, $ip_initial, $ip_last, $server){

      $array_object;
      $array_ip = [];
      $flag = 0;

      $object_data = DB::table('fw_objects')->where('name', '=', $object_name)->get();
      $object_data2 = json_decode(json_encode($object_data), true);

      $company_data = DB::table('fw_companies')->where('id', '=', $object_data2[0]['company_id'])->get();
      $company_data2 = json_decode(json_encode($company_data), true);

      $name_company = $company_data2[0]['name'];
      $token_company = $company_data2[0]['token_company'];

      $command = "tscpgw_api -g '".$server."' -a ranges -o ".$object_name;

      \SSH::into('checkpoint')->run($command, function($line2){
         Log::info($line2.PHP_EOL);
         $this->range = $line2.PHP_EOL;
      });

      while ( ($this->range == "") || ($flag >= 2) ) {
         if($flag >= 2) break;
         $flag++;
         \SSH::into('checkpoint')->run($command, function($line){
            Log::info($line.PHP_EOL);
            $this->range = $line.PHP_EOL;
         });
      }

      Log::info($this->range);

      if(!empty($this->range)){
         Log::info("esto es arraaaay");
         //EVALUAR SI ES ARRAY---------
         $body = explode("\n", $this->range);
         $array_object = array("fecha" => $body[0], "cantidad" => $body[1], "registros" => []);
         $i = 0;

         foreach($body as $key => $row){
            if($key > 1){
               if($row != ""){
                  $var1 = preg_replace('/\s+/', '-', $row);
                  array_push($array_object['registros'], $var1);
               }
            }
         }

         foreach($array_object['registros'] as $value) {
            $range = Range::parse($value);
            foreach($range as $ip){
               array_push($array_ip, (string)$ip);
            }
         }

         if(in_array($ip_initial, $array_ip) && in_array($ip_last, $array_ip)){
            Log::info("Existe la IP: ".$ip_initial);

            $response = array("response" => 1, "info" => "success", "token" => $token_company);

            return $response;
         }else{
            Log::info("No existe la ip: ".$ip_initial);
            $response = array("response" => 0, "info" => $this->range, "token" => $token_company);
            return $response;
         }
      }else{
         Log::info("VIENE VACIO EL ARRAY RANGE");
         $response = array("response" => 0, "info" => "No devuelve valor", "token" => $token_company);
         return $response;
      }
   }

   function remove_element($array,$value) {
      return array_diff($array, (is_array($value) ? $value : array($value)));
   }

   public function evaluateRemoveIp($object_name, $ip_initial, $ip_last, $server){

      /*$object_name = 'Object29Nov';
      $ip_initial = '105.105.1.10';
      $ip_last = '105.105.1.10';
      $server = '172.16.3.112';*/

      $array_object;
      $array_ip = [];
      $array_ip2 = [];
      $array_ip3 = [];
      $ips_save = [];
      $flag = 0;
      $temp_data_err = [];
      $temp_data_succ = [];
      $array_data_err = [];
      $array_data_succ = [];

      $command = "tscpgw_api -g '".$server."' -a ranges -o ".$object_name;
      Log::info($command);

      \SSH::into('checkpoint')->run($command, function($line2){
         Log::info($line2.PHP_EOL);
         $this->range = $line2.PHP_EOL;
      });

      while ( ($this->range == "") || ($flag >= 2) ) {
         if($flag >= 2) break;
         $flag++;
         \SSH::into('checkpoint')->run($command, function($line){
            Log::info($line.PHP_EOL);
            $this->range = $line.PHP_EOL;
         });
      }

      $body = explode("\n", $this->range);
      $array_object = array("fecha" => $body[0], "cantidad" => $body[1], "registros" => []);
      $i = 0;

      foreach($body as $key => $row){
         if($key > 1){
            if($row != ""){
               $var1 = preg_replace('/\s+/', '-', $row);
               array_push($array_object['registros'], $var1);
            }
         }
      }

      //Evaluar si es una sola ip o varias las que se eliminarán
      if($ip_initial == $ip_last){

         foreach($array_object['registros'] as $value) {

            $exist = Range::parse($value)->contains(new IP($ip_initial));

            if($exist){
               Log::info("excludeeee");
               Log::info($value);

               $ips_part = explode("-", $value);
               Log::info("ips parts");
               Log::info($ips_part);

               $range = Range::parse($value);
               foreach($range as $ip) {
                  array_push($array_ip, (string)$ip);
               }

               $array_ip_exist = $this->remove_element($array_ip, $ip_initial);
               Log::info("array_ip_exist");
               Log::info($array_ip_exist);

               //die();
               $count_array = count($array_ip_exist);

               //if($count_array > 0){
               //ELIMINO EL RANGO COMPLETO
               $removeRange = $this->removeRange($server, $object_name, $ips_part[0], $ips_part[1]);

               Log::info("removeRange****");
               Log::info($removeRange);

               if($removeRange['response'] == 1){//Significa que no se eliminó
                  $temp_data_err = array("server"=>$server, "object_name"=>$object_name, "ip_initial"=> $ips_part[0], "ip_last" => $ips_part[1], "type" => "delrip", "class" => "ip");
                  array_push($array_data_err, $temp_data_err);

                  //Guardaré en mongo los logs ya sean buenos o malos
                  $log = new HistoricalData;
                  $log->server = $server;
                  $log->object_name = $object_name;
                  $log->ip_initial = $ip_initial;
                  $log->ip_last = $ip_last;
                  $log->type = "delrip";
                  $log->class ="ip";
                  $log->status = 0;
                  $log->info = $removeRange['info'];
                  $log->token_company = $removeRange['token'];
                  $log->save();

               }else{//Si se eliminó
                  $temp_data_succ = array("server"=>$server, "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => 1, "current_ips" => 1);
                  array_push($array_data_succ, $temp_data_succ);

                  //Guardaré en mongo los logs ya sean buenos o malos
                  $log = new HistoricalData;
                  $log->server = $server;
                  $log->object_name = $object_name;
                  $log->ip_initial = $ip_initial;
                  $log->ip_last = $ip_last;
                  $log->type = "delrip";
                  $log->class ="ip";
                  $log->status = 1;
                  $log->info = $removeRange['info'];
                  $log->token_company = $removeRange['token'];
                  $log->save();
               }

               $responseRange = array("success" => $array_data_succ, "error" => $array_data_err, "info" => 1);
               $respuesta = array("new_range" => $array_ip_exist, "remove" => $removeRange);
               Log::info("La respuesta es: ");
               Log::info($respuesta);
               return $respuesta;

               /*}else{
                  Log::info("Se eliminó correctamente");
               }*/
            }
         }
      }else{//Significa que son varias ips a eliminar

         foreach($array_object['registros'] as $value) {
            //Buscar las ips del rango completo a eliminar*************
            if( (Range::parse($value)->contains(new IP($ip_initial))) && (Range::parse($value)->contains(new IP($ip_last))) ){
               $range = Range::parse($value);
               foreach($range as $ip) {
                  array_push($array_ip2, (string)$ip);
               }

               $ips_part = explode("-", $value);
               Log::info("ips parts");
               Log::info($ips_part);

               Log::info("array 2");
               Log::info($array_ip2);

               $range_delete = Range::parse($ip_initial.'-'.$ip_last);
               foreach($range_delete as $ip) {
                  array_push($array_ip3, (string)$ip);
               }

               Log::info("array 3");
               Log::info($array_ip3);

               foreach($array_ip2 as $row){
                  if(!in_array($row, $array_ip3)){
                     array_push($ips_save, $row);
                  }
               }
               //Debo mandar a guardar cada ip restante

               Log::info("ips save");
               Log::info($ips_save);

               $removeRange = $this->removeRange($server, $object_name, $ips_part[0], $ips_part[1]);

               Log::info("removeRange****");
               Log::info($removeRange);

               if($removeRange['response'] == 1){//Significa que no se eliminó
                  $temp_data_err = array("server"=>$server, "object_name"=>$object_name, "ip_initial"=> $ips_part[0], "ip_last" => $ips_part[1], "type" => "delrip", "class" => "ip");
                  array_push($array_data_err, $temp_data_err);

                  //Guardaré en mongo los logs ya sean buenos o malos
                  $log = new HistoricalData;
                  $log->server = $server;
                  $log->object_name = $object_name;
                  $log->ip_initial = $ip_initial;
                  $log->ip_last = $ip_last;
                  $log->type = "delrip";
                  $log->class ="ip";
                  $log->status = 0;
                  $log->info = $removeRange['info'];
                  $log->token_company = $removeRange['token'];
                  $log->save();

               }else{//Si se eliminó
                  $temp_data_succ = array("server"=>$server, "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => 1, "current_ips" => 1);
                  array_push($array_data_succ, $temp_data_succ);

                  //Guardaré en mongo los logs ya sean buenos o malos
                  $log = new HistoricalData;
                  $log->server = $server;
                  $log->object_name = $object_name;
                  $log->ip_initial = $ip_initial;
                  $log->ip_last = $ip_last;
                  $log->type = "delrip";
                  $log->class ="ip";
                  $log->status = 1;
                  $log->info = $removeRange['info'];
                  $log->token_company = $removeRange['token'];
                  $log->save();
               }

               $responseRange = array("success" => $array_data_succ, "error" => $array_data_err, "info" => 1);
               $respuesta = array("new_range" => $ips_save, "remove" => $removeRange);
               Log::info("La respuesta es: ");
               Log::info($respuesta);
               return $respuesta;
            }
         }
      }
   }

   public function removeRange($server, $object_name, $ip_initial, $ip_last){

      $ssh_command = "tscpgw_api -g '".$server."' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

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
         Log::info("try again removeRange");
         \SSH::into('checkpoint')->run($ssh_command, function($line){
            Log::info($line.PHP_EOL);
            $evaluate = $line.PHP_EOL;
         });
      }

      Log::info("flag removeRange");
      Log::info($flag);
      Log::info($evaluate);

      sleep(3);

      $evaluateRemoveRange = $this->existIpRange($object_name, $ip_initial, $ip_last, $server);

      return $evaluateRemoveRange;
   }

   public function agreggateNewRange($server, $object_name, $ip_initial, $ip_last){

      $ssh_command = "tscpgw_api -g '".$server."' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $flag = 0;
      $flag2 = 0;
      $array_data_err = [];
      $temp_data_err = [];
      $array_data_succ = [];
      $temp_data_succ = [];
      $condition;

      $verification = 1;

      $exist_object = $this->verifyExistObject($server, $object_name);

      if($exist_object == 1){

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

         $exist_range = $this->existIpRange($object_name, $ip_initial, $ip_last, $server);

         if($exist_range['response'] == 0){
            $temp_data_err = array("server"=>$server, "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
            array_push($array_data_err, $temp_data_err);

            //Guardaré en mongo los logs ya sean buenos o malos
            $log = new HistoricalData;
            $log->server = $server;
            $log->object_name = $object_name;
            $log->ip_initial = $ip_initial;
            $log->ip_last = $ip_last;
            $log->type = "addrip";
            $log->class ="ip";
            $log->status = 0;
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
            $log->save();

         }else{
            $temp_data_succ = array("server"=>$server, "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => 1, "current_ips" => 1);
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
            $log->info = $exist_range['info'];
            $log->token_company = $exist_range['token'];
            $log->save();
         }

         Log::info("112");
         Log::info($array_data_err);
         Log::info($array_data_succ);
      }else{
         Log::info("No existe el objeto en el ".$server);
      }

      $respuesta = array("success" => $array_data_succ, "error" => $array_data_err);
      return $respuesta;
   }

}
