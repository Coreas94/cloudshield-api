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
use GeoIP as GeoIP;

use App\Http\Controllers\CheckpointController;

use App\Company;
use App\CompanyPlan;
use App\Plans;
use App\DetailPlan;
use App\ServicesPlans;

class Controller extends BaseController{

   use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

   public function pruebaEmail(){
      return view('email.pruebaemail');
   }

   public function getIp(){

      //\Storage::makeDirectory('pruebaCarpeta', 777);

      //chmod("pruebaCarpeta", 0777);
      //\Storage::put('Otraprueba22'.'/'."jjj".'.json', "holaaa");

      //$test = \Storage::get('Otraprueba'.'/'."jjj".'.json');
      //return $test;
      /*$data_exist = json_decode(file_get_contents(storage_path() .'/app/Otraprueba/jjj'.'.json'), true);
      return $data_exist;*/
      //Log::info($data_exist);
   }

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
      $new_object_name = 'Object19Febrero';
      //$ip_initial = '198.198.198.5';
      //$ip_last = '198.198.198.5';
      $ip_initial = '170.5.11.5';
      $ip_last = '170.5.12.0';

      // $ssh_command2 = "tscpgw_api -g '172.16.3.112' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";
      //$ssh_command2 = "tscpgw_api -g '172.16.3.116' -a count -o ".$new_object_name;
      #$ssh_command2 = "tscpgw_api -g '172.16.3.113' -a ranges -o ".$new_object_name;
      $ssh_command2 = "tscpgw_api -g '172.16.3.112' -a search -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";

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

      $curl = curl_init();

      curl_setopt_array($curl, array(
         CURLOPT_URL => "http://172.16.3.35/MIkrotik/public/Sign?email=kr12%40red4g.net&password=123456",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => "",
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_SSL_VERIFYPEER => false,
         CURLOPT_SSL_VERIFYHOST => false,
         CURLOPT_CUSTOMREQUEST => "POST",
         CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
         ),
      ));

      $response = curl_exec($curl);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
         Log::info("cURL Error #:" . $err);
         $response_mk = 0;
      } else {
         $result = json_decode($response, true);
         Log::info($result);
         Log::info($result['token']);
      }
   }

   public function getErrorData(){
      $datos = HistoricalData::where('status', '=', 0)->delete();

      Log::info($datos);
   }

   public function changeErrorData(){

      $datos = HistoricalData::where('status', '=', 0)->update(['status' => 1]);

      //Guardaré en mongo los logs ya sean buenos o malos
      $log = new HistoricalData;
      $log->server = '172.16.3.112';
      $log->object_name = 'objeto-test';
      $log->ip_initial = '12.12.12.12';
      $log->ip_last = '12.12.12.12';
      $log->type = "delrip";
      $log->class ="ip";
      $log->status = 0;
      $log->info = 'manual';
      $log->token_company = 'ninguno';
      $log->save();

      Log::info(count($datos));
   }


   public function insertUserPayment(Request $request){

      $cust_pay = new CustomerPayment;
      $cust_pay->customer_name = "name";

   }

   public function setObject(){

      $checkpoint = new CheckpointController;

      if(Session::has('sid_session'))
 			$sid = Session::get('sid_session');
 		else $sid = $checkpoint->getLastSession();

      if($sid){

         $curl = curl_init();

			curl_setopt_array($curl, array(
			  	CURLOPT_URL => "https://172.16.3.114/web_api/set-dynamic-object",
			  	CURLOPT_RETURNTRANSFER => true,
			  	CURLOPT_ENCODING => "",
			  	CURLOPT_MAXREDIRS => 10,
			  	CURLOPT_TIMEOUT => 30,
			  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  	CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_POSTFIELDS => "{\r\n \"name\" : \"CUST-Cs150-IP-ADDRESS\",\r\n  \"read-only\" : \"true\"\r\n}",
			  	CURLOPT_HTTPHEADER => array(
			    	"cache-control: no-cache",
			    	"content-type: application/json",
			    	//"postman-token: 67baa239-ddc9-c7a4-fece-5a05f2396e38",
			    	"x-chkp-sid: ".$sid
			  	),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return response()->json([
					'error' => [
						'message' => $err,
						'status_code' => 20
					]
				]);
			}else{

 				$result = json_decode($response, true);
            Log::info($result);
            return $result;
         }
      }else{
         return "no hay sesion";
      }
   }

}
