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

use Illuminate\Database\Eloquent\SoftDeletes;

use Datatables;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;

use JWTAuth;

class FireWallController extends Controller{

	private $company_id;
	private $servers;
	private $connect;
	private $user;
	
	public function __construct(Request $request){
		//$this->middleware('auth');
   	#$this->user = JWTAuth::toUser($request['token']);
   	$this->company_id = $this->user['company_id'];
   	#$this->servers = self::servers();
   	$user = JWTAuth::toUser($request['token']);
	}

	public function servers(){
		Log::info("llega al servers");
      $server = FwCompanyServer::with(['server' => function($query){
         $query->select('url', 'key', 'format', 'type_id', 'id')
         ->with(['type_server' => function($query){
            $query->select('name', 'id');
         }]);
      }])->where('company_id', $this->company_id)->get();

      $server->transform(function($item){
         $collect = [
            'description' => $item->description,
            'url' => $item->server->url,
            'key' => $item->server->key,
            'format' => $item->server->format,
            'type' => $item->server->type_server->name
         ];

         return $collect;
		});

      return $server;
 	}

	public function getAllObjects(Request $request){

		$objects = DB::table('fw_objects')
						->join('fw_servers', 'fw_objects.server_id', '=', 'fw_servers.id')
						->join('fw_object_types', 'fw_objects.type_object_id', '=', 'fw_object_types.id')
						->select('fw_objects.id', 'fw_objects.name', 'fw_servers.name AS server', 'fw_object_types.name AS type')
						->get();

		return response()->json([
			'data' => $objects,
		]);
	}

	public function redirectCreateObject(Request $request, CheckpointController $checkpoint, PaloAltoController $paloalto){

		if($request['server'] == 0){
			#$value = $this->createObjects($request, $checkpoint);
			$value = $this->createListObj($request, $checkpoint);

			return $value;

		}elseif($request['server'] == 1){
			$value = $checkpoint->addObjectCompany($request);

			return $value;

		}elseif($request['server'] == 2){
			$value = $paloalto->saveAddressObject($request);

			return $value;
		}
	}

	public function createObjects(Request $request, $checkpoint){

		$server_ch = 1;
		$server_pa = 2;
		$result_ch;
		$result_pa;

		$object_name = $request['object_name'];
		$comment = "Prueba code";
		$ip_initial = $request['ip_init'];
		$ip_last = $request['ip_last'];

		$user = JWTAuth::toUser($request['token']);
		$company_id = $user['company_id'];

		$sid = $checkpoint->getLastSession();

		if($sid){

			$new_object_name = 'ch_'.$object_name;

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://172.16.3.114/web_api/add-dynamic-object",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CUSTOMREQUEST => "POST",
				//CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$object_name\",\r\n  \"comments\" : \"$comment\",\r\n  \"color\" : \"$color\"\r\n}",
				CURLOPT_POSTFIELDS => "{\r\n  \"name\" : \"$new_object_name\",\r\n  \"comments\" : \"$comment\"\r\n}",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"X-chkp-sid: ".$sid
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if($err){
				return response()->json([
					'error' => [
						'message' => $err,
						'status_code' => 20
					]
				]);
			}else{
				$result = json_decode($response, true);
				Log::info($result);
				Log::info($result['uid']);
				$uid = $result['uid'];

				$object_type = 4; //Es object dynamic

				$object_new = New FwObject;
				$object_new->name = $new_object_name;
				$object_new->uid = $uid;
				$object_new->type_object_id = $object_type;
				$object_new->server_id = $server_ch;
				$object_new->company_id = $company_id;
				$object_new->save();

				if($object_new->id){
					Log::info("Se creó el objeto checkpoint");

					$bd_obj_check = DB::connection('checkpoint')->table('object_list')->insert(['name' => $new_object_name, 'description' => $comment]);

					if($bd_obj_check){
						Log::info("Se guardó en la bd checkpoint");
					}else{
						Log::info("No se guardó en bd checkpoint");
					}

					$ssh_command = 'tscpgw_api -g "172.16.3.113" -a adddyo -o '.$new_object_name;
					\SSH::into('checkpoint')->run($ssh_command, function($line){
						Log::info($line.PHP_EOL);
					});

					$publish = $checkpoint->publishChanges($sid);

					if($publish == 'success'){
						Log::info("publish success");
						$object_id = $object_new->id;
						$type_address_id = 7;//Pertenece a rango de ip para checkpoint
						#$ip_address = $ip_initial.'-'.$ip_last;

						$ssh_command = "tscpgw_api -g '172.16.3.113' -a addrip -o ".$new_object_name." -r ".$ip_initial." ".$ip_last;
						\SSH::into('checkpoint')->run($ssh_command, function($line){
							Log::info($line.PHP_EOL);
						});

						Log::info("ip agregada ch");
						$addr_obj = new AddressObject;
						$addr_obj->ip_initial = $ip_initial;
						$addr_obj->ip_last = $ip_last;
						$addr_obj->object_id = $object_id;
						$addr_obj->type_address_id = $type_address_id;
						$addr_obj->save();

						if($addr_obj){
							$bd_ips_check = DB::connection('checkpoint')->table('ip_object_list')->insert(['object_id' => $object_id, 'ip_initial' => $ip_initial, 'ip_last' => $ip_last, 'created_at' =>  \Carbon\Carbon::now(),
							'updated_at' => \Carbon\Carbon::now()]);

							if($bd_ips_check){
								Log::info("insert ch");
								$result_ch = 1;
								/*return response()->json([
									'success' => [
										'data' => "¡IP guardada exitosamente en checkpoint!",
										'object_id' => $object_new->id,
										'status_code' => 200
									]
								]);*/
							}else{
								$result_ch = 0;
								/*return response()->json([
									'error' => [
										'message' => 'error al guardar la IP en checkpoint',
										'status_code' => 20
									]
								]);*/
							}
						}else{
							$result_ch = 0;
							/*return response()->json([
								'error' => [
									'message' => 'error al guardar la IP en checkpoint',
									'status_code' => 20
								]
							]);*/
						}

					}else{
						$result_ch = 0;
						/*return response()->json([
							'message' => 'error al publicar el objeto en checkpoint',
							'status_code' => 20
						]);*/
					}
				}else{
					$result_ch = 0;
					Log::info("No se creó el objeto");

					/*return response()->json([
						'message' => 'error al guardar el objeto en checkpoint',
						'status_code' => 20
					]);*/
				}
			}

			/*************COMIENZO A GUARDAR EL OBJETO EN PALO ALTO***************/
			Log::info("Llega a palo alto");
			$object_data = DB::table('fw_address_types')->get();
			$obj = collect($object_data);			
			$company_data = DB::table('fw_companies')->where('id', $user['company_id'])->get();

		  	/*if($role_user == 'superadmin'){
				$company_data = DB::table('fw_companies')->where('tag', $tag_input)->get();
		  	}else{
				$company_data = DB::table('fw_companies')->where('id', $user['company_id'])->get();
		  	}*/

			$company_data = json_encode($company_data);
		  	$company_data2 = json_decode($company_data, true);
		  	$tag = $company_data2[0]['tag'];

			$name_address = 'pa_'.$object_name;

			#$name_address = $request['name_address'];
			#$ip = $request['ip_address'];
			$ip = $ip_initial.'-'.$ip_last;
			$type_address_id = 2;//Pertenece a rango de ip para palo alto
			$type_address = "ip-range";

			$filter = $obj->where('name', $type_address, false);
		  	$object = $filter->filter(function($item) {
			  	return [$item->id, $item->type_object_id];
			})->first();

			Log::info("llega a guzzle");

		  	$client = new \GuzzleHttp\Client();
		  	$data = $client->post("https://172.16.3.150/api?key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09&type=config&action=set&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/address/entry[@name='".$name_address."']&element=<".$type_address.">".$ip."</".$type_address."><tag><member>".$tag."</member></tag>", ["verify" => false]);
			$response = $data->getBody()->getContents();

			Log::info(print_r($response, true));

			$xml = new \SimpleXMLElement($response);

			$code = $xml['code'];

		  	if ($code == 19 || $code == 20) {
				Session::flash('success', trans('policies.address_created'));
				Log::info("Se creó el objeto palo alto");

				$data_object['name'] = $name_address;
			  	$data_object['type_object_id'] = $object->type_object_id;
			  	$data_object['tag'] = $tag;
				$data_object['company_id'] = $company_id;
				$data_object['server_id'] = $server_pa;
			  	$data_object['created_at'] = \Carbon\Carbon::now()->toDateTimeString();
			  	$data_object['updated_at'] = \Carbon\Carbon::now()->toDateTimeString();

				$id = DB::table('fw_objects')->insertGetId($data_object);
				Log::info("insert pa obj");

			  	$data_address['ip_initial'] = $ip_initial;
				$data_address['ip_last'] = $ip_last;
			  	$data_address['object_id'] = $id;
			  	$data_address['type_address_id'] = $type_address_id;
			  	$data_address['created_at'] = \Carbon\Carbon::now()->toDateTimeString();
			  	$data_address['updated_at'] = \Carbon\Carbon::now()->toDateTimeString();

				DB::table('fw_address_objects')->insert($data_address);
				Log::info("insert pa addr");

				$result_pa = 1;

			  	/*return response()->json([
				  	'success' => [
					  'message' => 'address created successfully',
					  'status_code' => 200
					]
			  	]);*/
		  	}else{
				$result_pa = 0;

			  	/*return response()->json([
				  	'success' => [
					  	'message' => 'address not created',
					  	'status_code' => 20
				  	]
			  	]);*/
			}

			Log::info("resutlado 1 ".$result_ch);
			Log::info("resutlado 2 ".$result_pa);

			if($result_ch == 1 && $result_pa == 1){
				Log::info("creado en todos");
				return response()->json([
					'success' => [
						'message' => 'Objeto creado en todos los servers.',
						'object_id' => $object_new->id,
						'status_code' => 200
					]
				]);
			}elseif($result_ch == 1 && $result_pa == 0) {
				Log::info("creado en ch");
				return response()->json([
					'success' => [
						'message' => 'Objeto creado en Checkpoint pero no en Palo Alto',
						'status_code' => 200
					]
				]);
			}elseif($result_ch == 0 && $result_pa == 1) {
				Log::info("creado en pa");
				return response()->json([
					'success' => [
						'message' => 'Objeto creado en Palo Alto pero no en Checkpoint',
						'status_code' => 200
					]
				]);
			}else{
				Log::info("no creado en ninguno");
				return response()->json([
					'message' => 'No pudo ser creado el objeto',
					'status_code' => 20
				]);
			}

		}else{
			Log::info("no existe sid");
			return response()->json([
				'message' => 'No existe sid',
				'status_code' => 20
			]);
		}
	}

	public function createObjectsCh($data, CheckpointController $checkpoint){

		$server_id = 1;

		$tag = $data['tag'];
		$company_id = $data['company_id'];

		$arrObject = array(
			0 => array(
				'object_name' => 'CUST-'.$tag.'-WHITELIST-INCOMING',
				'server_id' => $server_id,
				'company_id' => $company_id,
				'tag' => $tag
			),
			1 => array(
				'object_name' => 'CUST-'.$tag.'-WHITELIST-OUTGOING',
				'server_id' => $server_id,
				'company_id' => $company_id,
				'tag' => $tag
			),
			2 => array(
				'object_name' => 'CUST-'.$tag.'-BLACKLIST-INCOMING',
				'server_id' => $server_id,
				'company_id' => $company_id,
				'tag' => $tag
			),
			3 => array(
				'object_name' => 'CUST-'.$tag.'-BLACKLIST-OUTGOING',
				'server_id' => $server_id,
				'company_id' => $company_id,
				'tag' => $tag
			),
			4 => array(
				'object_name' => 'CUST-'.$tag.'-IP-ADDRESS',
				'server_id' => $server_id,
				'company_id' => $company_id,
				'tag' => $tag
			)
		);

		$i = 0;
		$arr = [];
		foreach($arrObject as $row){
			#Log::info("ROWWWWW");
			#Log::info($row);
			$response = $checkpoint->addObjectCompany($row); 
			$arr[$i] = $response;

			$i++;
		}

		if(!in_array("error", $arr)){
			return "success";
		}else{
			return "error";
		}
		#return $arr;
	}

	public function createRules($data, CheckpointController $checkpoint){

		$rule = array(
			0=> array(
				'name' => "WHITELIST-INCOMING",
				'section' => $section,
				'source' => "CUST-".$tag."-WHITELIST-INCOMING",
				'destination' => "CUST-".$tag."-IP-ADDRESS",
				'vpn' => "Any",
				'action' => "Accept"
			),
			1 => array(
				'name' => "WHITELIST-OUTGOING",
				'section' => $section,
				'source' => "CUST-".$tag."-IP-ADDRESS",
				'destination' => "CUST-".$tag."WHITELIST-OUTGOING",
				'vpn' => "Any",
				'action' => "Accept"
			),
			2 => array(
				'name' => "BLACKLIST-INCOMING",
				'section' => $section,
				'source' => "CUST-".$tag."BLACLIST-INCOMING",
				'destination' => "CUST-".$tag."IP-ADDRESS",
				'vpn' => "Any",
				"action" => "Drop"
			),
			3 => array(
				'name' => "BLACKLIST-OUTGOING",
				'section' => $section,
				'source' => "CUST-".$tag."IP-ADDRESS",
				'destination' => "CUST-".$tag."BLACKLIST-OUTGOING",
				'vpn' => "Any",
				'action' => "Drop"
			),
		);
	}

}
