<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Requests;
use phpseclib\Net\SFTP;
use App\Jobs\senderEmailIp;
use Mail;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

use JWTAuth;
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

class CheckpointController extends Controller
{
    private $output = "";
    private $typeResponseCurl = 1;
    public function test(){
        /*return Control::curl("172.16.3.114")
        ->config(array(
            "user" => "test",
            "password" => "test"
        ))->eCurl();*/
        return 'ok';

    }
    //Comienza desde aqui
    public function __construct(){
    		Session::forget('sid_session');
    		$evaluate = "";
  	}
    public function servers(){
        $server = FwServer::where('id', 1)->get();
        $server->transform(function($item){
            $collect = [
      				'id' => $item->id,
      				//'description' => $item->description,
      			  'name' => $item->name,
              'url' => $item->url,
              'user' => $item->user,
              'password' => $item->password,
               	//'key' => $item->server->key,
                //'type' => $item->server->type_server->name
             ];
             return $collect;
        });
        return $server;
    }
    public function loginCheckpoint(){
        $servers = self::servers();
        $user = $servers[0]['user'];
        $password = $servers[0]['password'];

        Control::curl("172.16.3.114")
          ->config([
              'user' => $user,
              'password' => $password
          ])
          ->eCurl(function($response){
              $this->output = $response;
              $this->typeResponseCurl = 1;
          }, function($error){
              $this->output = $error;
              $this->typeResponseCurl = 0;
          });
        if (!$this->typeResponseCurl){
            return response()->json([
                'error' => [
                    'message' => $err,
                    'status_code' => 20
                ]
            ]);
        }else{
            $result = json_decode($this->output, true);
            $sid = $result['sid'];
            Session::put('sid_session', $sid);
            return $sid;
        }
    }
    public function getLastSession(){
    		$servers = self::servers();
    		$user = $servers[0]['user'];
    		$password = $servers[0]['password'];

        Control::curl("172.16.3.114")
          ->config([
              'user' => $user,
              'password' => $password,
              'continue-last-session' => true
          ])->eCurl(function($response){
              $this->output = $response;
              $this->typeResponseCurl = 1;
          }, function($error){
              $this->output = $error;
              $this->typeResponseCurl = 0;
        });
    		if (!$this->typeResponseCurl) $sid = $this->loginCheckpoint();
    		else {
      			$result = json_decode($this->output, true);
      			if(isset($result['code'])){
      				//Log::info($result['code']);
      				if($result['code'] == "err_login_failed_more_than_one_opened_session")
      					$sid = $this->loginCheckpoint();
      				else
      					$sid = $this->loginCheckpoint();
      			}else $sid = $result['sid'];
    		}
    		Session::put('sid_session', $sid);
    		return $sid;
  	}
    public function publishChanges($sid){
        Control::curl("172.16.3.114")
          ->is('publish')
          ->sid($sid)
          ->eCurl(function($response){
              $this->output = $response;
              $this->typeResponseCurl = 1;
          }, function($error){
              $this->output = $error;
              $this->typeResponseCurl = 0;
        });
    		if(!$this->typeResponseCurl) return "error";
    		else return "success";
  	}
    public function discardChanges(){
    		$sid = Session::get('sid_session');
        return Control::curl("172.16.3.114")
          ->is('discard')
          ->sid($sid)
          ->eCurl();
    }
    public function installPolicy(){
        if(Session::has('sid_session'))
          $sid = Session::get('sid_session');
        else $sid = $this->getLastSession();

        if($sid){
          Control::curl("172.16.3.114")
            ->is('install-policy')
            ->config([
                'policy-package' => 'standard',
                'access' => true,
                'threat-prevention' => true,
                'target' => ['CLUSTER-IP-REPUTATION']
            ])
            ->sid($sid)
            ->eCurl(function($response){
                $this->output = $response;
                $this->typeResponseCurl = 1;
            }, function($error){
                $this->output = $error;
                $this->typeResponseCurl = 0;
            });
          if(!$this->typeResponseCurl){
            //Log::info("error en el curl");
            return "error";
          }else{
            $resp = json_decode($this->output, true);
            if(isset($resp['task-id'])){
                $task = $resp['task-id'];
                $result_task = $this->showTask($task);

                foreach($result_task['tasks'] as $key => $value){
                  if($value['status'] == "succeeded")
                      return "success";
                  else
                      return "error";
                }
              }else return "error";
          }
        }else return "error con el sid";
    }
    public function showTask($task_id){
  		$percentage = 0;
  		if(Session::has('sid_session'))
  			$sid = Session::get('sid_session');
  		else $sid = $this->getLastSession();
      $response = "";
  		while($percentage != 100) {
          Control::curl("172.16.3.114")
            ->is('show-task')
            ->config([
                'task-id' => $task_id
            ])
            ->sid($sid)
            ->eCurl(function($response){
                $this->output = $response;
                $this->typeResponseCurl = 1;
            }, function($error){
                $this->output = $error;
                $this->typeResponseCurl = 0;
            });

          if($this->typeResponseCurl){
              $response = json_decode($this->output, true);
        			foreach($response['tasks'] as $row)
        				$percentage = $row['progress-percentage'];
          }
  		}
  		return $response;
  	}
}
