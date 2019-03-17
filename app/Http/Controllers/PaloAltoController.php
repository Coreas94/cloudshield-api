<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class PaloAltoController extends Controller{

   public function addAddressObject(Request $request){

      $data_object = [];
    	$data_address = [];

    	try {

    		$userLog = JWTAuth::toUser($request['token']);
			$role_user = $userLog->roles->first()->name;
			$server_ch = 2; //Es el id de palo alto

			$company_id = $request['company_id'];

			$company_data = DB::table('fw_companies')->where('id', $company_id)->get();
			$company_data2 = json_decode(json_encode($company_data), true);
			$tag = $company_data2[0]['tag'];

    		$name_object = $request['name_object'];
			$name_address = 'CUST-'.$tag.'-'.$object_name;
    		$ip_initial = $request['ip_initial'];
			$ip_last = $request['ip_last']
    		$type_address = 'ip-range';

			$client = new \GuzzleHttp\Client();
			$data = $client->post("https://172.16.3.150/api?key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09&type=config&action=set&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/address/entry[@name='".$name_address."']&element=<".$type_address.">".$ip_initial."-".$ip_last."</".$type_address."><tag><member>".$tag."</member></tag>", ["verify" => false]);
			$response = $data->getBody()->getContents();

			$xml = new \SimpleXMLElement($response);
			$code = $xml['code'];

			if ($code == 19 || $code == 20) {

				$object_new = New FwObject;
				$object_new->name = $new_object_name;
				$object_new->uid = $uid;
				$object_new->type_object_id = $object_type;
				$object_new->server_id = $server_ch;
				$object_new->company_id = $company_id;
				$object_new->tag = $tag;
				$object_new->save();

				if($object_new){
					$addr_obj = new AddressObject;
					$addr_obj->ip_initial = $ip_initial;
					$addr_obj->ip_last = $ip_last;
					$addr_obj->object_id = $object_id;
					$addr_obj->type_address_id = $type_address_id;
					$addr_obj->save();

					if($addr_obj){
						return response()->json([
							'success' => [
								'message' => "Objeto creado exitosamente",
								'status_code' => 200
							]
						]);
					}else{
						return response()->json([
							'success' => [
								'message' => "Se creó el objeto pero no las ips",
								'status_code' => 200
							]
						]);
					}

				}else{
					return response()->json([
						'error' => [
							'message' => "El objeto no pudo ser creado",
							'status_code' => 20
						]
					]);
				}

			}else{

				return response()->json([
					'success' => [
						'message' => 'El objeto no puede ser creado',
						'status_code' => 20
					]
				]);
			}
		}
		catch (GuzzleHttp\Exception\ClientException $e) {
		   $response = $e->getResponse();
		   $responseBodyAsString = $response->getBody()->getContents();

			return response()->json([
				'error' => [
					'message' => $responseBodyAsString,
					'status_code' => 20
				]
			]);
		}

   }

}
