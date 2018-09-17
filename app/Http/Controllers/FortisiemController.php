<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Datatables;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;
use App\LogsData;

use JWTAuth;

class FortisiemController extends Controller
{

   public function getOrganizations(){

      $process = new Process("python ".app_path()."/api_py/GetMonitoredOrganizations.py");
      $process->run();

      // executes after the command finishes
      if (!$process->isSuccessful()) {
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);

      Log::info($result);
      return $result;
   }

   public function getIncidents(Request $request){

      $process = new Process("python ".app_path()."/api_py/GetIncidentsByOrg.py Super");
      $process->run();

      // executes after the command finishes
      if (!$process->isSuccessful()) {
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info($result);
      return $result;
   }

   public function saveNewOrganization(Request $request){

      $process = new Process("python ".app_path()."/api_py/AddOrg.py ejemplo.xml");
      $process->run();

      if(!$process->isSuccessful()){
         Log::info("is error");
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info($result);
      return $result;
   }

   public function runScriptLogs(){
      $dt = \Carbon\Carbon::now();
      $fecha_fin = $dt->timestamp;
      $fecha_inicio = $dt->subHour(3);
      $fecha_inicio = $fecha_inicio->timestamp;

      $process = new Process("python ".app_path()."/api_py/GetQueryResultsByOrg.py ".app_path()."/api_py/request.xml ".$fecha_inicio.' '. $fecha_fin);
      $process->run();

      if(!$process->isSuccessful()){
         Log::info("is error");
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info(count($result));
      //$insertData = LogsData::insert($result);

      foreach ($result as $key => $value) {
         $array = json_decode($value, true);

         $format_date = date('Y-m-d H:i:s', strtotime($array['phRecvTime']));
         #$test = explode(",", $array['rawEventMsg']);

         #Log::info($array['rawEventMsg']);

         $log = new LogsData;
         $log->receive_time = $format_date;
         $log->event_type = $array['eventType'];
         $log->event_name = $array['eventName'];
         $log->src_ip = $array['srcIpAddr'];
         $log->src_country = $array['srcGeoCountry'];
         $log->src_latitude = $array['srcGeoLatitude'];
         $log->src_longitude = $array['srcGeoLongitude'];
         $log->dst_ip = $array['destIpAddr'];
         $log->dst_country = $array['destGeoCountry'];
         $log->dst_latitude = $array['destGeoLatitude'];
         $log->dst_longitude = $array['destGeoLongitude'];
         $log->rule_name = "vacio";
         $log->event_log = $array['rawEventMsg'];
         $log->relaying_ip = $array['relayDevIpAddr'];
         $log->date_initial = $array['phRecvTime'];
         $log->save();

         $list = ['38.103.38.6', '38.103.38.102', '38.103.38.72'];

         $logs = LogsData::whereIn('dst_ip', $list)->orWhereIn('src_ip', $list)->orderBy('receive_time', 'desc')->take(10000)->get();

         \Storage::put('user_test/file.json', $logs);

      }
   }

   public function getDataLogs(Request $request){

      $list = ['38.103.38.6', '38.103.38.102', '38.103.38.72'];

      $logs = LogsData::whereIn('dst_ip', $list)->orWhereIn('src_ip', $list)->get();

      Log::info(count($logs));

      if(count($logs) > 0){
         return response()->json([
            'success' => [
              'data' => $logs,
              'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
              'message' => "No data",
              'status_code' => 20
            ]
         ]);
      }
   }

   public function readJsonFile(Request $request){

      $path = storage_path() . "/app/user_test/file.json"; // ie: /var/www/laravel/app/storage/json/filename.json

      $json = json_decode(file_get_contents($path), true);

      if(count($json) > 0){
         return response()->json([
            'success' => [
              'data' => $json,
              'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
              'message' => "No data",
              'status_code' => 20
            ]
         ]);
      }

   }

}
