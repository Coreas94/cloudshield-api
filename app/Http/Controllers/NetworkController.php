<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\ObjectAddr;
use App\Policies;
use App\Company;
use App\PoliciesSource;
use App\PoliciesDestination;
use App\FwCompanyServer;
use App\FwObject;
use App\AddressObject;
use App\Http\Controllers\CheckPointFunctionController;

use Illuminate\Database\Eloquent\SoftDeletes;

use Datatables;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;

use JWTAuth;

class NetworkController extends Controller{

   public function createGroup(Request $request){

      $checkpoint = new CheckpointController;
      $checkpoint2 = new CheckPointFunctionController;

      if(Session::has('sid_session'))
         $sid = Session::get('sid_session');
      else $sid = $checkpoint->getLastSession();

      if($sid){

         $user = JWTAuth::toUser($request['token']);

         $company_id = $user['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);

         $tag = $company_data2[0]['tag'];
         $server_id = 1;

   		$arrayGroup = array(
   			0 => array(
   				'group_name' => 'CUST-'.$tag.'-NET-GROUP-IPS-ALOW',
   				'server_id' => $server_id,
   				'company_id' => $company_id,
   				'tag' => $tag,
   				'token' => $request['token']
   			),
   			1 => array(
   				'group_name' => 'CUST-'.$tag.'-NET-GROUP-IPS-WHITELIST',
   				'server_id' => $server_id,
   				'company_id' => $company_id,
   				'tag' => $tag,
   				'token' => $request['token']
   			)
   		);

         foreach($arrayGroup as $value) {
            $tag = $value['tag'];
            $group_name = $value['group_name'];
            $server_id = $value['server_id'];
            $company_id = $value['company_id'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
               CURLOPT_URL => "https://172.16.3.114/web_api/add-group",
            	CURLOPT_RETURNTRANSFER => true,
            	CURLOPT_ENCODING => "",
            	CURLOPT_MAXREDIRS => 10,
            	CURLOPT_TIMEOUT => 30,
            	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            	CURLOPT_SSL_VERIFYPEER => false,
            	CURLOPT_SSL_VERIFYHOST => false,
            	CURLOPT_CUSTOMREQUEST => "POST",
            	//CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$object_name\",\r\n  \"comments\" : \"$comment\",\r\n  \"color\" : \"$color\"\r\n}",
            	CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$group_name\",\r\n  \"tags\" : [ \"$tag\"]\r\n}",
            	CURLOPT_HTTPHEADER => array(
            		"cache-control: no-cache",
            		"content-type: application/json",
            		"X-chkp-sid: ".$sid
            	),
            ));

            $response = curl_exec($curl);
            sleep(2);
            $err = curl_error($curl);

            curl_close($curl);

            if($err){
            	return "error";
            }else{

               $result = json_decode($response, true);
               Log::info("Resultado obj 114");
     				Log::info($result);

     				if(isset($result['code'])){
     					Log::info($result['code']);

                  if($result['code'] == "err_validation_failed"){
    						return "error";
    					}
     				}else{

                  $create2 = $checkpoint2->createGroup($data);
                  sleep(2);

     					$uid = $result['uid'];

     					$new_group = New FwGroup;
     					$new_group->name = $group_name;
     					$new_group->uid = $uid;
     					$new_group->server_id = $server_ch;
     					$new_group->company_id = $company_id;
     					$new_group->tag = $tag;
     					$new_group->save();

                  if($object_new->id){
     						return "success";
                  }else return "error";
               }
            }
         }
      }else{
         return "error";
      }
   }

   public function createNetwork(){

   }

















   public function getDownload(Request $request) {
      // prepare content
      $company = Company::all();
      $content = "Logs \n";
      foreach ($company as $log) {
         $content .= "ID: ".$log->id;
         $content .= "\n";
         $content .= "Name: ".$log->name;
         $content .= "\n";
      }

      // file name that will be used in the download
      $fileName = "logs.txt";

      // use headers in order to generate the download
      $headers = [
         'Content-type' => 'text/plain',
         'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
         'Content-Length' => sizeof($content)
      ];

      // make a response, with the content, a 200 response code and the headers
      return \Response::make($content, 200, $headers);
   }

}
