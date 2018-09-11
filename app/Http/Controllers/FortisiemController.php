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

   public function runReport(){
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
      return $result;
   }
}
