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
      $fecha_inicio = $dt->subHour(2);
      $fecha_inicio = $fecha_inicio->timestamp;

      $process = new Process("python ".app_path()."/api_py/GetQueryResultsByOrg.py ".app_path()."/api_py/request.xml ".$fecha_inicio.' '. $fecha_fin);
      $process->run();

      if(!$process->isSuccessful()){
         Log::info("is error");
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info("trae: ". count($result));

      foreach ($result as $key => $value) {
         $array = json_decode($value, true);
         // Log::info("ARRAAAAY");
         // Log::info($array);

         $format_date = date('Y-m-d H:i:s', strtotime($array['phRecvTime']));
         $test = explode("[rule_name]=", $array['rawEventMsg']);

         $rule_name;
         foreach($test as $k => $v){
            if($k == 1){
               $text_msg = explode(",",$v);
               $rule_name = $text_msg[0];
            }else{
               $rule_name = "no-exist";
            }
         }

         if(isset($array['srcIpAddr']) && isset($array['destIpAddr'])){

            $log = new LogsData;
            $log->receive_time = $format_date;
            $log->event_type = $array['eventType'];
            $log->event_name = $array['eventName'];
            $log->src_ip = isset($array['srcIpAddr']) ? $array['srcIpAddr'] : 'undefined';
            $log->severity_category = isset($array['eventSeverityCat']) ? $array['eventSeverityCat'] : 'undefined';
            $log->src_country = isset($array['srcGeoCountry']) ? $array['srcGeoCountry'] : 'undefined';
            $log->src_latitude = isset($array['srcGeoLatitude']) ? $array['srcGeoLatitude'] : 'undefined';
            $log->src_longitude = isset($array['srcGeoLongitude']) ? $array['srcGeoLongitude'] : 'undefined';
            $log->dst_ip = isset($array['destIpAddr']) ? $array['destIpAddr'] : 'undefined';
            $log->dst_country = isset($array['destGeoCountry']) ? $array['destGeoCountry'] : 'undefined';
            $log->dst_latitude = isset($array['destGeoLatitude']) ? $array['destGeoLatitude'] : 'undefined';
            $log->dst_longitude = isset($array['destGeoLongitude']) ? $array['destGeoLongitude'] : 'undefined';
            $log->rule_name = $rule_name;
            $log->event_log = $array['rawEventMsg'];
            $log->relaying_ip = $array['relayDevIpAddr'];
            $log->date_initial = $array['phRecvTime'];
            $log->save();
         }
      }
   }

   public function getDataLogs(Request $request){

      $userLog = JWTAuth::toUser($request['token']);
      $company_id = $userLog['company_id'];

      $ranges_ip = DB::table('fw_address_objects')->join('fw_objects', 'fw_objects.id', '=', 'fw_address_objects.object_id')->where('fw_objects.company_id', '=', $company_id)->get(['ip_initial', 'ip_last']);
      $ranges_ip = json_decode(json_encode($ranges_ip), true);

      // Log::info($ranges_ip);
      $new_array_ip = [];
      foreach($ranges_ip as $val){
         $range = Range::parse($val['ip_initial'].'-'.$val['ip_last']);
         foreach($range as $ip) {
         	array_push($new_array_ip, (string)$ip);
         }
      }

      // $logs = LogsData::whereIn('dst_ip', $new_array_ip)->orWhereIn('src_ip', $new_array_ip)->orderBy('receive_time', 'desc')->take(5000)->get();
      $logs = LogsData::orderBy('receive_time', 'desc')->take(5000)->get();
      //Log::info($logs);

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

      $path = storage_path() ."/app/user_test/file.json";
      $json = json_decode(file_get_contents($path), true);

      if(count($json) > 0){
         return response()->json([
            'success' => [
               'data' => $json,
               'status_code' => 200
            ]
         ]);
      }else{

         $response = $this->getDataLogs();

         return response()->json([
            'error' => [
               'message' => "No data",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function getDataFiltered(Request $request){
      Log::info($request);

      $filter_type = $request['type'];
      $fecha = date('Y-m-j');
      $date_value = date('Y-m-d H:i:s');
      $userLog = JWTAuth::toUser($request['token']);
      $company_id = $userLog['company_id'];
      /*
      0 -> 12hour
      1-> 1day
      2-> 3days
      3 -> custom
      */
      switch ($filter_type) {
         case '0':

            $date = new \DateTime();
            $date->modify('-12 hours');
            $initial_date = $date->format('Y-m-d H:i:s');

            Log::info($initial_date);

            break;
         case '1':
            $nuevafecha = strtotime ('-1 day', strtotime($fecha));
            $initial_date = date ('Y-m-d H:i:s', $nuevafecha);
            break;
         case '2':
            $nuevafecha = strtotime ('-3 day', strtotime($fecha));
            $initial_date = date ('Y-m-d H:i:s', $nuevafecha);
            break;

         case '3':
            $initial = $request['initial'];
            $last = $request['last'];

            $format = "Y-m-d H:i:s"; //or something else that date() accepts as a format
            $format2 = "Y-m-d"; //or something else that date() accepts as a format
            $initial_date = date_format(date_create($initial), $format);
            $date_value = date_format(date_create($last), $format2);
            $date_value = $date_value.' 23:59:00';

            break;
         default:
            die();
            break;
      }

      /*$ranges_ip = DB::table('fw_address_objects')->join('fw_objects', 'fw_objects.id', '=', 'fw_address_objects.object_id')->where('fw_objects.company_id', '=', $company_id)->get(['ip_initial', 'ip_last']);
      $ranges_ip = json_decode(json_encode($ranges_ip), true);

      $new_array_ip = [];
      foreach($ranges_ip as $val){

         $range = Range::parse($val['ip_initial'].'-'.$val['ip_last']);
         foreach($range as $ip) {
         	array_push($new_array_ip, (string)$ip);
         }
      }*/

      //$logs = LogsData::whereIn('dst_ip', $new_array_ip)->orWhereIn('src_ip', $new_array_ip)->whereBetween('receive_time', array($initial_date, $date_value))->orderBy('receive_time', 'desc')->take(8000)->get();
      $logs = LogsData::whereBetween('receive_time', array($initial_date, $date_value))->orderBy('receive_time', 'desc')->get();
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

   public function countAttacksDay(Request $request){

      $fecha = date('Y-m-j');
      $date_init = $fecha.' 00:00:00';
      $date_last = $fecha.' 23:59:59';

      $logs = LogsData::whereBetween('receive_time', array($date_init, $date_last))->orderBy('receive_time', 'desc')->count();
      Log::info("el count es:");
      Log::info($logs);

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

}
