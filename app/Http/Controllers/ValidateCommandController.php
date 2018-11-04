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

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;

class ValidateCommandController extends Controller{
   private $output = "";
   private $verification;

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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      Log::info("112");
      Log::info($array_data_err);
      Log::info($array_data_succ);

		sleep(2);

      $flag = 0;
      $flag2 = 0;
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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      Log::info("113");
      Log::info($array_data_err);
      Log::info($array_data_succ);

      sleep(2);

      $flag = 0;
      $flag2 = 0;
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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      Log::info("116");
      Log::info($array_data_err);
      Log::info($array_data_succ);

      sleep(2);

      $flag = 0;
      $flag2 = 0;
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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      sleep(2);

      Log::info("117");
      Log::info($array_data_err);
      Log::info($array_data_succ);

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

      $condition;
      $verification = 1;

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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
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
      }else{
         $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      sleep(2);

      $arreglo = array("success" => $array_data_succ, "error" => $array_data_err, "info" => $total_ips);

      return $arreglo;
   }

}
