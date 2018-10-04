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

class Controller extends BaseController
{
   use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

   public function prueba2(){

      $data = Session::get('data_tmp');
      Log::info($data);

      /*$object_name = 'ObjetoParaBorrar';
      $ip_initial = '198.198.198.1';
      $ip_last = '198.198.198.2';
      $array_data = [];

      $temp_data = array("server"=>"172.16.3.117", "object_name"=>$object_name, "ip_initial"=> $ip_initial, "ip_last" => $ip_last, "type" => "addrip");
      array_push($array_data, $temp_data);

      Session::put('data_tmp2', $array_data);


      \Artisan::call('checkpoint:resendData');*/
      /*$new_object_name = 'ObjetoParaBorrar';
      $ip_initial = '198.198.198.5';
      $ip_last = '198.198.198.1';

      $ssh_command2 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$new_object_name." -r '".$ip_initial." ".$ip_last."'";
      //$ssh_command2 = 'tscpgw_api -g "172.16.3.112" -a adddyo -o '.$new_object_name;

      Log::info($ssh_command2);
		//$ssh_command3 = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$new_object_name." -r ".$ip_initial." ".$ip_last;

		\SSH::into('checkpoint')->run($ssh_command2, function($line){
			Log::info($line.PHP_EOL);
			//$evaluate = $line.PHP_EOL;
		});*/
   }

   public function test(){

      $hosts = [
         'host' => '172.16.3.151',
         'port' => '9200',
         'scheme' => 'http',
         'user' => '',
         'pass' => ''
      ];

      $client_config = [
         'hosts' => [
            'https://172.16.3.151:9200'
         ],
         'retries' => 0
      ];

      $client = ClientBuilder::fromConfig($client_config);
      //$result = json_decode($client, true);
      // $client = ClientBuilder::create() //Instantiate a new ClientBuilder
      //    ->setHosts($hosts) //Set the hosts
      //    ->build(); //Build the client object

      //$return = \Elasticsearch::connection('elastic')->index($data);
      Log::info(print_r($client, true));
      print_r($client, true);
   }
}
