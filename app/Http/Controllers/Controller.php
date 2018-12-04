<?php

namespace App\Http\Controllers;

use Elasticsearch\ClientBuilder;
require 'vendor/autoload.php';

//use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use phpseclib\Net\SFTP;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Artisan;
use App\HistoricalData;

class Controller extends BaseController
{
   use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

   public function prueba2(Request $request){

      /*$log = new HistoricalData;
      $log->server = "190.190.190.190";
      $log->object_name = "insertObject";
      $log->ip_initial = "150.150.150.150";
      $log->ip_last = "150.150.150.150";
      $log->type = "addrip";
      $log->class ="ip";
      $log->status = 0;
      $log->save();*/

      //\Storage::makeDirectory('holis', 777);
      /*$object_name = 'ObjetoParaBorrar';
      $ip_initial = '198.198.198.1';
      $ip_last = '198.198.198.2';
      $array_data = [];
      $temp_data = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip");
      array_push($array_data, $temp_data);

      Session::put('data_tmp2', $array_data);

      \Artisan::call('checkpoint:resendData');*/
      $new_object_name = 'Object29Nov';
      //$ip_initial = '198.198.198.5';
      //$ip_last = '198.198.198.5';
      $ip_initial = '105.105.1.11';
      $ip_last = '105.105.1.11';

      // $ssh_command2 = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";
      //$ssh_command2 = "tscpgw_api -g '172.16.3.116' -a count -o ".$new_object_name;
      #$ssh_command2 = "tscpgw_api -g '172.16.3.113' -a ranges -o ".$new_object_name;
      $ssh_command2 = "tscpgw_api -g '172.16.3.117' -a search -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";

      //$ssh_command2 = 'tscpgw_api -g "172.16.3.112" -a adddyo -o '.$new_object_name;
      //$ssh_command2 = 'tscpgw_api -g 172.16.3.116 -a searchobj -o '.$new_object_name;

      Log::info($ssh_command2);
		//$ssh_command3 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$new_object_name." -r ".$ip_initial." ".$ip_last;

		\SSH::into('checkpoint')->run($ssh_command2, function($line){
			Log::info($line.PHP_EOL);
			//$evaluate = $line.PHP_EOL;
		});
   }

   public function test(){

      $data_token = array('token' => '');
      $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEsImlzcyI6Imh0dHA6Ly9jbG91ZHNoaWVsZC5yZWQ1Zy5jb20vY29udHJvbDRfYXBpMi9hcGkvdjIvYXV0aC9hcGlfbG9naW4iLCJpYXQiOjE1NDE0NDU0OTQsImV4cCI6MTU0MTQ3Nzg5NCwibmJmIjoxNTQxNDQ1NDk0LCJqdGkiOiI5aW5OdHEycjk3dzdrbkV2In0.lJOrXXUpiMg6sYDijupWG3hmCTi7jT_akDdnTLIrJkM";

      //$this->dispatchFrom('App\Commands\resendDataCheckpoint', $data_token);
      Artisan::call('checkpoint:resendData');
      // Artisan::call('checkpoint:resendData', ['token' => $token]);
   }

   public function getErrorData(){

      $datos = HistoricalData::where('status', '=', 0)->delete();

      Log::info($datos);
   }

   public function changeErrorData(){

      $datos = HistoricalData::where('status', '=', 0)->update(['status' => 1]);

      Log::info($datos);
   }


}
