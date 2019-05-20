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
use App\ThreatIps;
use GeoIP as GeoIP;

use JWTAuth;

class FortisiemController extends Controller{

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

         if(strpos($rule_name, 'THREATSTOP') !== false){
            $rule_name_sust = "IP REPUTATION";
         }else{
            $rule_name_sust = $rule_name;
         }

         if( (isset($array['srcIpAddr']) && $array['srcIpAddr'] != "no-exist") && (isset($array['destIpAddr']) && $array['destIpAddr'] != "no-exist") ){

            if(isset($array['srcGeoCountry']) &&  $array['srcGeoCountry'] != "no-exist"){
               $srcCountry = $array['srcGeoCountry'];
            }else{
               $geo = GeoIP::getLocation($array['srcIpAddr']);
               $geo = $geo->toArray();
               $srcCountry = $geo['country'];
               $src_city = $geo['city'];
            }

            if(isset($array['srcGeoLatitude']) && $array['srcGeoLatitude'] != "no-exist"){
               $srcLat = $array['srcGeoLatitude'];
            }else{
               $geo = GeoIP::getLocation($array['srcIpAddr']);
               $geo = $geo->toArray();
               $srcLat = $geo['lat'];
            }

            if(isset($array['srcGeoLongitude']) && $array['srcGeoLongitude'] != "no-exist"){
               $srcLong = $array['srcGeoLongitude'];
            }else{
               $geo = GeoIP::getLocation($array['srcIpAddr']);
               $geo = $geo->toArray();
               $srcLong = $geo['lon'];
            }

            if(isset($array['destGeoCountry']) && $array['destGeoCountry'] != "no-exist"){
               $dstCountry = $array['destGeoCountry'];
            }else{
               $geo = GeoIP::getLocation($array['destIpAddr']);
               $geo = $geo->toArray();
               $dstCountry = $geo['country'];
               $dst_city = $geo['city'];
            }

            if(isset($array['destGeoLatitude']) && $array['destGeoLatitude'] != "no-exist"){
               $dstLat = $array['destGeoLatitude'];
            }else{
               $geo = GeoIP::getLocation($array['destIpAddr']);
               $geo = $geo->toArray();
               $dstLat = $geo['lat'];
            }

            if(isset($array['destGeoLongitude']) && $array['destGeoLongitude'] != "no-exist"){
               $dstLong = $array['destGeoLongitude'];
            }else{
               $geo = GeoIP::getLocation($array['destIpAddr']);
               $geo = $geo->toArray();
               $dstLong = $geo['lon'];
            }

            if(!isset($array['ipsProtectionName'])){
               $raw = explode("[attack]=", $array['rawEventMsg']);
               foreach($raw as $k => $v){
                  if($k == 1){
                     $raw_msg = explode(",",$v);
                     $attack_name = $raw_msg[0];
                  }else{
                     $attack_name = "undefined";
                  }
               }
            }else{
               $attack_name = "undefined";
            }

            if(!isset($array['ipsProtectionName'])){
               $raw = explode("[Protection Name]=", $array['rawEventMsg']);

               foreach($raw as $k => $v){
                  if($k == 1){
                     $raw_msg = explode(",",$v);
                     $protection_name = $raw_msg[0];
                  }else{
                     $protection_name = "undefined";
                  }
               }
            }else{
               $protection_name = "undefined";
            }

            $log = new LogsData;
            $log->receive_time = $format_date;
            $log->event_type = $array['eventType'];
            $log->event_name = $array['eventName'];
            $log->src_ip = isset($array['srcIpAddr']) ? $array['srcIpAddr'] : 'undefined';
            $log->severity_category = isset($array['eventSeverityCat']) ? $array['eventSeverityCat'] : 'undefined';
            $log->protection_name = isset($array['ipsProtectionName']) ? $array['ipsProtectionName'] : $protection_name;
            $log->attack_name = isset($array['attackName']) ? $array['attackName'] : $attack_name;
            $log->src_country = $srcCountry;
            $log->src_latitude = $srcLat;
            $log->src_longitude = $srcLong;
            $log->src_city = isset($src_city) ? $src_city : 'undefined';
            $log->dst_ip = isset($array['destIpAddr']) ? $array['destIpAddr'] : 'undefined';
            $log->dst_country = $dstCountry;
            $log->dst_latitude = $dstLat;
            $log->dst_longitude = $dstLong;
            $log->dst_city = isset($dst_city) ? $dst_city : 'undefined';
            $log->rule_name = $rule_name;
            $log->event_log = $array['rawEventMsg'];
            $log->relaying_ip = $array['relayDevIpAddr'];
            $log->date_initial = $array['phRecvTime'];
            $log->rule_name_sust = isset($rule_name_sust) ? $rule_name_sust : $rule_name;
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
         foreach($range as $ip){
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

            break;
         case '1':
            $nuevafecha = strtotime ('-1 day', strtotime($fecha));
            $initial_date = date ('Y-m-d H:i:s', $nuevafecha);

            break;
         case '2':
            Log::info("entra al case 2");
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

      $logs = LogsData::whereBetween('receive_time', array($initial_date, $date_value))->orderBy('receive_time', 'desc')->take(5000)->get();
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

      $fecha = date('Y-m-d');
      $date_init = $fecha.' 00:00:00';
      $date_last = $fecha.' 23:59:59';

      $logs = LogsData::whereBetween('receive_time', array($date_init, $date_last))->count();
      // $logs = LogsData::whereBetween('receive_time', array($date_init, $date_last))->orderBy('receive_time', 'desc')->count();
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

   public function runAutomaticLogs(){
      $dt = \Carbon\Carbon::now();
      $fecha_fin = $dt->timestamp;
      $fecha_inicio = $dt->subMinutes(10);
      $fecha_inicio = $fecha_inicio->timestamp;

      $process = new Process("python ".app_path()."/api_py/getCheckpointData.py ".app_path()."/api_py/checkpoint_medium.xml ".$fecha_inicio.' '. $fecha_fin);
      $process->run();

      if(!$process->isSuccessful()){
         Log::info("is error");
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info("trae checkpoint: ". count($result));
      Log::info($result);

      $array2 = array_unique($result, SORT_REGULAR);

      foreach ($array2 as $key => $value) {
         $array = json_decode($value, true);

         if(!empty($array) && isset($array['srcIpAddr']) && $array['srcIpAddr'] != "no-exist"){
            // $format_date = date('Y-m-d H:i:s', strtotime($array['phRecvTime']));

            $ip_exist = ThreatIps::where('ip', '=', $array['srcIpAddr'])->first();
            if ($ip_exist === null) {
               $ips = new ThreatIps;
               $ips->ip = $array['srcIpAddr'];
               $ips->object_name = 'checkpoint-block';
               //$ips->receive_time = $format_date;
               $ips->status = 0;
               $ips->created_at = $dt;
               $ips->updated_at = $dt;
               $ips->save();
            }
         }else{
            return "No records found";
         }
      }
   }

   public function runAutoPaloAltoLogs(){

      $dt = \Carbon\Carbon::now();
      $fecha_fin = $dt->timestamp;
      $fecha_inicio = $dt->subMinutes(10);
      $fecha_inicio = $fecha_inicio->timestamp;

      $process = new Process("python ".app_path()."/api_py/getPAData.py ".app_path()."/api_py/paloalto_data.xml ".$fecha_inicio.' '. $fecha_fin);
      $process->run();

      if(!$process->isSuccessful()){
         Log::info("is error");
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info("trae palo alto: ". count($result));
      Log::info($result);

      $array2 = array_unique($result, SORT_REGULAR);

      foreach ($array2 as $key => $value) {
         $array = json_decode($value, true);

         if(!empty($array) && isset($array['srcIpAddr']) && $array['srcIpAddr'] != "no-exist"){
            // $format_date = date('Y-m-d H:i:s', strtotime($array['phRecvTime']));

            /*$ip_exist = ThreatIps::where('ip', '=', $array['srcIpAddr'])->first();
            if ($ip_exist === null) {
               $ips = new ThreatIps;
               $ips->ip = $array['srcIpAddr'];
               $ips->object_name = 'soc-5g-block';
               //$ips->receive_time = $format_date;
               $ips->status = 0;
               $ips->created_at = $dt;
               $ips->updated_at = $dt;
               $ips->save();
            }*/
         }else{
            return "No records found";
         }
      }
   }

   public function runScriptPA(){

      $dt = \Carbon\Carbon::now();
      $fecha_fin = $dt->timestamp;
      $fecha_inicio = $dt->subHour(2);
      $fecha_inicio = $fecha_inicio->timestamp;

      $process = new Process("python ".app_path()."/api_py/GetQueryPaloAlto.py ".app_path()."/api_py/pa_script.xml ".$fecha_inicio.' '. $fecha_fin);
      $process->run();

      if(!$process->isSuccessful()){
         Log::info("is error");
         throw new ProcessFailedException($process);
      }

      $result = json_decode($process->getOutput(), true);
      Log::info("trae PAlto: ". count($result));
      Log::info($result);
   }

}
