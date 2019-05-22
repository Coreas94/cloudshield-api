<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use phpseclib\Net\SFTP;
use App\Jobs\senderEmailIp;
use App\Jobs\BackgroundTask;
use Mail;
use File;
use Illuminate\Foundation\Bus\DispatchesJobs;

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

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\Http\Control;
use Carbon\Carbon;

use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\CheckPointFunctionController;

class TemporaryController extends Controller
{
   private $output = "";

    public function RemoveIpTemp($objects){

      foreach($objects as $row){

         $object_name = $row['name'];
   		$ip_initial = $row['ip_initial'];
   		$ip_last = $row['ip_last'];
         $id_address = $row['id_address'];
   		$evaluate = "";

   		$total_ips = 1;
   		$flag = 0;

         $ssh_command = "tscpgw_api -g '172.16.3.112' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
         $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a delrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

         \SSH::into('checkpoint')->run($ssh_command, function($line){
   			Log::info($line.PHP_EOL);
   			$this->output = $line.PHP_EOL;
   		});

   		$evaluate = $this->output;

   		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
   			if($flag >= 3) break;
            $flag++;
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
   			$this->output = $line2.PHP_EOL;
   		});

   		$evaluate = $this->output;

   		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
   			if($flag >= 3) break;
            $flag++;
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
   			$this->output = $line3.PHP_EOL;
   		});

   		$evaluate = $this->output;

   		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
   			if($flag >= 3) break;
            $flag++;
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
   			$this->output = $line4.PHP_EOL;
   		});

   		$evaluate = $this->output;

   		while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
   			if($flag >= 3) break;
            $flag++;
   			Log::info("1 existe try again 117");
   			\SSH::into('checkpoint')->run($ssh_command4, function($line4){
   				Log::info($line4.PHP_EOL);
   				$evaluate = $line4.PHP_EOL;
   			});
   		}

   		sleep(2);

         //Borro ese registro
         $del_ip = AddressObject::find($id_address);
         $del_ip->delete();
      }
   }

   public function disableRule($rules){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         foreach($rules as $value) {

            $uid_rule = $value['uid'];
   			$status = false;

            Control::curl("172.16.3.114")
            ->is("set-access-rule")
            ->config([
               'uid' => $uid_rule,
               'layer' => 'Network',
               'enabled' => $status
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
     				$publish = $checkpoint->publishChanges($sid);

               if($publish == "success"){

                  $disable2 = $this->disableRule2($rules);

                  Log::info("se deshabilito");
               }else{
                  Log::info("no se deshabilito");
               }
   			}
         }
 		}else{
         Log::info("no se deshabilito");
      }
   }

   public function disableRule2($rules){

      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $checkpoint2->getLastSession();

 		if($sid && $sid != "empty"){

         foreach($rules as $value) {

            $uid_rule = $value['uid'];
            $name_rule = $value['name'];
   			$status = false;

            Control::curl("172.16.3.118")
            ->is("set-access-rule")
            ->config([
               'name' => $name_rule,
               'layer' => 'Network',
               'enabled' => $status
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
     				$publish = $checkpoint2->publishChanges($sid);

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

   public function addIpTemp($objects){

      foreach($objects as $row){

        $object_name = $row['name'];
        $ip_initial = $row['ip_initial'];
        $ip_last = $row['ip_last'];
        $id_address = $row['id_address'];
        $evaluate = "";

        $total_ips = 1;
        $flag = 0;

        $ssh_command = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
        $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
        $ssh_command3 = "tscpgw_api -g '172.16.3.116' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";
        $ssh_command4 = "tscpgw_api -g '172.16.3.117' -a addrip -o ".$object_name." -r '".$ip_initial." ".$ip_last."'";

        \SSH::into('checkpoint')->run($ssh_command, function($line){
           Log::info($line.PHP_EOL);
           $this->output = $line.PHP_EOL;
        });

        $evaluate = $this->output;

        while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
           if($flag >= 3) break;
           $flag++;
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
           $this->output = $line2.PHP_EOL;
        });

        $evaluate = $this->output;

        while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
           if($flag >= 3) break;
           $flag++;
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
           $this->output = $line3.PHP_EOL;
        });

        $evaluate = $this->output;

        while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
           if($flag >= 3) break;
           $flag++;
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
           $this->output = $line4.PHP_EOL;
        });

        $evaluate = $this->output;

        while (stripos($evaluate, "try again") !== false || stripos($evaluate, "failed") !== false || ($flag >= 3)) {
           if($flag >= 3) break;
           $flag++;
           Log::info("1 existe try again 117");
           \SSH::into('checkpoint')->run($ssh_command4, function($line4){
              Log::info($line4.PHP_EOL);
              $evaluate = $line4.PHP_EOL;
           });
        }

         sleep(2);

         //restauro ese registro
         $restore_ip = AddressObject::withTrashed()->where('id', '=', $id_address)->restore();

         return $restore_ip;
      }
   }

   public function enableRules($rules){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

 		if($sid){

         foreach($rules as $value) {

            $uid_rule = $value['uid'];
   			$status = true;

            Control::curl("172.16.3.114")
            ->is("set-access-rule")
            ->config([
               'uid' => $uid_rule,
               'layer' => 'Network',
               'enabled' => $status
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
     				$publish = $checkpoint->publishChanges($sid);

               if($publish == "success"){

                  $enable2 = $this->enableRule2($rules);

                  Log::info("se habilito");
                  return "success";
               }else{
                  Log::info("no se habilito");
                  return "error";
               }
   			}
         }
 		}else{
         Log::info("no se habilito");
         return "error";
      }
   }

   public function enableRule2($rules){

      $checkpoint2 = new CheckPointFunctionController;

 		if(Session::has('sid_session_2'))
 			$sid = Session::get('sid_session_2');
 		else $sid = $checkpoint2->getLastSession();

 		if($sid && $sid != "empty"){

         foreach($rules as $value) {

            $uid_rule = $value['uid'];
            $name_rule = $value['name'];
   			$status = true;

            Control::curl("172.16.3.118")
            ->is("set-access-rule")
            ->config([
               'name' => $name_rule,
               'layer' => 'Network',
               'enabled' => $status
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
     				$publish = $checkpoint2->publishChanges($sid);

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

}
