<?php

namespace App\Http;

class Control {

    protected static $ip = "127.0.0.1";
    protected static $config = "{ }";
    protected static $sslVerifyPeer = false;
    protected static $sslVerifyHost = false;
    protected static $returnTransfer = true;
    protected static $request = "POST";
    protected static $maxRedir = 10;
    protected static $timeOut = 30;
    //protected static $postmanToken = "67baa239-ddc9-c7a4-fece-5a05f2396e38";
    protected static $sid = "";
    protected static $is = "login";

    public static function curl($ipAddress){
        Control::$ip = $ipAddress;
        return (new Control)->run();
    }
    public function run(){
        return $this;
    }
    public function config($config){
        Control::$config = json_encode($config, true);
        return $this;
    }
    public function sslVerification($sslVerifyPeer, $sslVerifyHost){
        Control::$sslVerifyPeer = $sslVerifyPeer;
        Control::$sslVerifyHost = $sslVerifyHost;
        return $this;
    }
    public function request($type){
        Control::$request = $type;
        return $this;
    }
    public function max(...$max){
        if(isset($max[0]))
            Control::$maxRedir = !empty($max[0]) ? $max[0] : Control::$maxRedir;
        if(isset($max[1]))
            Control::$timeOut = !empty($max[0]) ? $max[0] : Control::$timeOut;
        return $this;;
    }
    public function sid($sid){
        Control::$sid = $sid;
        return $this;
    }
    public function is($is){
        Control::$is = $is;
        return $this;
    }
    public function returnTrans($ret){
        Control::$returnTransfer = $ret;
        return $this;
    }
    public function get(){
        return array(
            'ip' => Control::$ip,
            'is' => Control::$is,
            'ssl' => array(
                "vp" => Control::$sslVerifyPeer,
                "vh" => Control::$sslVerifyHost
            ),
            'redir' => Control::$maxRedir,
            'timeout' => Control::$timeOut,
            'request' => Control::$request,
            'fields' => Control::$config,
            'sid' => Control::$sid,
            'transfer' => Control::$returnTransfer
        );
    }
    public function eCurl(){
        $get = $this->get();

        $curl = curl_init();
        curl_setopt_array($curl, array(
    			CURLOPT_URL => "https://{$get['ip']}/web_api/{$get['is']}",
    			CURLOPT_RETURNTRANSFER => $get['transfer'],
    			CURLOPT_ENCODING => "",
    			CURLOPT_MAXREDIRS => $get['redir'],
    			CURLOPT_TIMEOUT => $get['timeout'],
    			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    			CURLOPT_SSL_VERIFYPEER => $get['ssl']['vp'],
    			CURLOPT_SSL_VERIFYHOST => $get['ssl']['vh'],
    			CURLOPT_CUSTOMREQUEST => $get['request'],
    			CURLOPT_POSTFIELDS => $get['fields'],
    			CURLOPT_HTTPHEADER => array(
      				"cache-control: no-cache",
      				"content-type: application/json",
              "X-chkp-sid: {$get['sid']}"
    			),
    		));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if($err) return $err;
        else $response;
    }

    protected static $cmd = "";
    protected static $hosts = [];

    public static function ssh($handl){
        if(is_array($handl)){
            $body = isset($handl[0]) ? $handl[0] : "127.0.0.*";
            if(isset($handl[1])){
                if(is_array($handl[1])){
                    foreach($handl[1] as $val)
                      array_push(Control::$hosts, str_replace("*", $val, $body));
                } else array_push(Control::$hosts, $body);
            } else array_push(Control::$hosts, $body);
        } else return false;
        return (new Control)->run();
    }
    public function addObject($name){
        Control::$cmd = "-a adddyo -o {$name}";
        return $this;
    }
    public function eSSH($callback){
        foreach (Control::$hosts as $host){
            $instaceCommand = Control::$cmd;
            $cmd = "tscpgw_api -g \"{$host}\" {$instaceCommand}";
            \SSH::into('checkpoint')->run($cmd, function($response){
                $callback($response, $cmd);
						});
            sleep(2);
        }
    }
}
