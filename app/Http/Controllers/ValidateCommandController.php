<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

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

class ValidateCommandController extends Controller{
   private $output = "";

   public function __construct(){
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
      $array_data_err = [];
      $temp_data_err = [];
      $array_data_succ = [];
      $temp_data_succ = [];

		\SSH::into('checkpoint')->run($ssh_command, function($line){
			Log::info($line.PHP_EOL);
			$evaluate = $line.PHP_EOL;
		});

      $evaluate = $this->output;

      #while ( ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag > 2)) {
		while ( (stripos($evaluate, "completed successfully") !== true ) || ($flag > 2)) {

         $flag++;
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

      if($flag >= 2){
         $temp_data_err = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }else{
         $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

		sleep(2);

      $flag = 0;
		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
			Log::info($line2.PHP_EOL);
			$evaluate = $line2.PHP_EOL;
		});

      $evaluate = $this->output;

		//while ( ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag > 2)) {
      while ( (stripos($evaluate, "completed successfully") !== true ) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
				Log::info($line2.PHP_EOL);
				$evaluate = $line2.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }else{
         $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      sleep(2);

      $flag = 0;
		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
			Log::info($line3.PHP_EOL);
			$evaluate = $line3.PHP_EOL;
		});

      $evaluate = $this->output;

		//while ( ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag > 2)) {
      while ( (stripos($evaluate, "completed successfully") !== true ) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
				Log::info($line3.PHP_EOL);
				$evaluate = $line3.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }else{
         $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      sleep(2);

      $flag = 0;
		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
			Log::info($line4.PHP_EOL);
			$evaluate = $line4.PHP_EOL;
		});

      $evaluate = $this->output;

		//while ( ( (stripos($evaluate, "try again") !== false) || (stripos($evaluate, "not found") !== false) || (stripos($evaluate, "Illegal IP") !== false) ) || ($flag > 2)) {
      while ( (stripos($evaluate, "completed successfully") !== true ) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
				Log::info($line4.PHP_EOL);
				$evaluate = $line4.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }else{
         $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }

      sleep(2);

      $arreglo = array("success" => $array_data_succ, "error" => $array_data_err, "info" => $total_ips);

      return $arreglo;
   }

   public function resendDataTemp(){

      $data = Session::get('temp_data_err');
      Log::info($data);
      $evaluate = "";

      if(!empty($data)){
         foreach ($data as $key => $value) {

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
            }
         }

         Session::forget('data_tmp');
      }else{
         Log::info("No hay nada en session");
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

   public function validateRemoveIpObject($object_name, $ip_initial, $ip_last){

      $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
      $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

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

		while ((stripos($evaluate, "try again") !== false) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 112");
			\SSH::into('checkpoint')->run($ssh_command, function($line){
				Log::info($line.PHP_EOL);
				$evaluate = $line.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }/*else{
         $temp_data_succ = array("server"=>"172.16.3.112", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }*/

		sleep(2);

      $flag = 0;
		\SSH::into('checkpoint')->run($ssh_command2, function($line2){
			Log::info($line2.PHP_EOL);
			$evaluate = $line2.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 113");
			\SSH::into('checkpoint')->run($ssh_command2, function($line2){
				Log::info($line2.PHP_EOL);
				$evaluate = $line2.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }/*else{
         $temp_data_succ = array("server"=>"172.16.3.113", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }*/

      sleep(2);

      $flag = 0;
		\SSH::into('checkpoint')->run($ssh_command3, function($line3){
			Log::info($line3.PHP_EOL);
			$evaluate = $line3.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 116");
			\SSH::into('checkpoint')->run($ssh_command3, function($line3){
				Log::info($line3.PHP_EOL);
				$evaluate = $line3.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }/*else{
         $temp_data_succ = array("server"=>"172.16.3.116", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }*/

      sleep(2);

      $flag = 0;
		\SSH::into('checkpoint')->run($ssh_command4, function($line4){
			Log::info($line4.PHP_EOL);
			$evaluate = $line4.PHP_EOL;
		});

      $evaluate = $this->output;

		while ((stripos($evaluate, "try again") !== false) || ($flag > 2)) {
         $flag++;
			Log::info("1 existe try again 117");
			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
				Log::info($line4.PHP_EOL);
				$evaluate = $line4.PHP_EOL;
			});
		}

      if($flag > 2){
         $temp_data_err = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip");
         array_push($array_data_err, $temp_data_err);
      }/*else{
         $temp_data_succ = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "delrip", "class" => "ip", "total_ips" => $total_ips, "current_ips" => $current_ips);
         array_push($array_data_succ, $temp_data_succ);
      }*/

      sleep(2);

      Session::put('temp_data_err', $array_data_err);
      //Session::put('temp_data_succ', $array_data_succ);

      return "Datos borrados";
   }

}
