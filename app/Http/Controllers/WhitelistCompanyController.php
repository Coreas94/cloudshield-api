<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection; // to generate collections.
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Company;
use App\User;
use App\BlockedIp;
use App\WhitelistCompany;

use JWTAuth;

class WhitelistCompanyController extends Controller{

   public function getIpsCompany(Request $request){
      $company_id = $request['company_id'];

      $ips = WhitelistCompany::where('company_id', '=', $company_id)->get();

      if(count($ips) > 0){
         return response()->json([
            'success' => [
               'data' => $ips,
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'success' => [
               'data' => [],
               'status_code' => 20
            ]
         ]);
      }
   }

   public function addIpsWhitelist(Request $request){

      $ip = $request['whitelist_ip'];
      $company_id = $request['company_id'];

      $new_ip = new WhitelistCompany;
      $new_ip->ip_allow = $ip;
      $new_ip->company_id = $company_id;
      $new_ip->save();

      if($new_ip){
         return response()->json([
            'success' => [
               'message' => "IP agregada con éxito",
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => "IP no pudo ser agregada",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function editIpWhitelist(Request $request){

      $id = $request['id_ip'];
      $ip = $request['ip_edit'];

      $update_ip = WhitelistCompany::where('id', $id)
         ->update(['ip_allow' => $ip]);

      if($update_ip){
         return response()->json([
            'success' => [
               'message' => "IP editada correctamente",
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => "IP no pudo ser editada",
               'status_code' => 20
            ]
         ]);
      }
   }

   public function deleteWhitelistIp(Request $request){

      $id_ip = $request['id_ip'];

      $delete = WhitelistCompany::find($id_ip);
		$delete->delete();

      if($delete){
         return response()->json([
            'success' => [
               'message' => "IP eliminada correctamente",
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => "IP no pudo ser eliminada",
               'status_code' => 20
            ]
         ]);
      }
   }

}
