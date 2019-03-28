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
use App\FwAccessRule;
use App\FwSectionAccess;
use App\FwObject;
use App\BlockedIp;
use App\WhitelistCompany;
use App\CompanyPlan;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\CheckPointFunctionController;

use JWTAuth;
use Datatables;
use Hash;

class AccessController extends Controller{

	public function getDataCompanies(Request $request){

		$userLog = JWTAuth::toUser($request['token']);
	  	$role_user = $userLog->roles->first()->name;

     	if($role_user == "superadmin" || $role_user == "ventas"){
        	$companies = DB::table('fw_companies')->where('deleted_at', NULL)->get();
     	}else{
        	$companies = DB::table('fw_companies')->where('deleted_at', NULL)->where('id', $userLog['company_id'])->get();
     	}

		$array_company = [];
		$i = 0;

		$company_data = json_encode($companies);
		$company_data2 = json_decode($company_data, true);

		foreach ($company_data2 as $key => $value) {
			$array_company[$i]['id'] = $value['id'];
			$array_company[$i]['account'] = $value['account'];
			$array_company[$i]['text'] = $value['name'];
			$array_company[$i]['tag'] = $value['tag'];
			$array_company[$i]['email'] = $value['email'];
			$array_company[$i]['phone'] = $value['phone'];
			$array_company[$i]['description'] = $value['description'];
			$array_company[$i]['address'] = $value['address'];
			$array_company[$i]['country_id'] = $value['country_id'];

			$i++;
		}

		$arreglo = new Collection($array_company);
		//return response()->json($arreglo);
		return response()->json([
			'success' => [
				'data' => $arreglo,
				'status_code' => 200
			]
	 	]);
	}

	public function addCompany(Request $request, CheckpointController $checkpoint, FireWallController $firewall){
		Log::info($request);
		/*return response()->json([
			'success' => [
				//'tag_company' => $tag,
				'message' => 'respuesta test',
				'status_code' => 200
			]
		]);*/

		//die();

		$checkpoint2 = new CheckPointFunctionController;
		$network = new NetworkController;
		$emailCtrl = new EmailController;

		$v = Validator::make($request->all(), [
			"name_new_company" => "required",
      	"address_new_company" => "required",
      	"email_new_company" => "required|email",
			"phone_new_company" => "required|numeric",

			"name_new_user" => "required",
			"new_username" => "required",
			"email_new_user" => "required|email",
			"phone_new_user" => "required|numeric",
			"password_new_user" => "required|min:4",
    	]);

     	if($v->fails()){
			return response()->json([
			 	'error' => [
				 	'data' => $v->errors(),
				 	'status_code' => 20
			 	]
			]);
    	}else{

			$token_company = "";
			$length = 10;
			$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
			$codeAlphabet.= "0123456789";
			$max = strlen($codeAlphabet); // edited

			for ($i=0; $i < $length; $i++) {
			  $token_company .= $codeAlphabet[random_int(0, $max-1)];
			}

	    	$name = $request['name_new_company'];
	    	$address = $request['address_new_company'];
	    	$email = $request['email_new_company'];
	    	$phone = $request['phone_new_company'];
	    	$description = isset($request['description_new_company']) ? $request['description_new_company'] : "";
			$token = $request['token'];
			$ips_assigned[] = $request['assigned_ips'];
			$user_mikrotik = $request['user_mikrotik'];

			$whitelist_ip = $request['whitelist_ip'];

			foreach($ips_assigned as $value){
				$ip_initial_mk = $value['ip_init'];
				$ip_last_mk = $value['ip_last'];
			}

    		$words = explode(" ", $name);
			$acronym = "";

			foreach ($words as $w) {
			  	$acronym .= $w[0];
			}

			$random = rand(100, 999);
			$random2 = rand(0,99);
	    	$account = '000'.$random;
	    	$tag = $acronym.''.$random;
			$tag_mk = $tag.''.$random2;
			$country_id = $request['country_new_company'];

			/*Mandaré a guardar el tag cuando se crea la compañía*/
			try{
				$client = new \GuzzleHttp\Client();
				$data = $client->post("https://172.16.3.150/api?key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09&type=config&action=set&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/tag/entry[@name='".$tag."']&element=<comments>Tag de la compañía: ".$name."</comments>", ["verify" => false]);
				$response = $data->getBody()->getContents();

				$xml = new \SimpleXMLElement($response);
				$code = $xml['code'];

				$tagCheck = $checkpoint->createTag($tag);
				sleep(2);

				$createtag2 = $checkpoint2->createTag2($tag);
				sleep(2);

				if(($code == 19 || $code == 20) && $tagCheck == "success"){
					$company = new Company;
					$company->name = $name;
					$company->address = $address;
					$company->email = $email;
					$company->phone = $phone;
					$company->description = $description;
					$company->account = $account;
					$company->tag = $tag;
					$company->country_id = $country_id;
					$company->token_company = $token_company;
					$company->save();

					if($company->id){
						$companyid = $company->id;

						$dataArray = array(
							"name" => $name,
							"tag" => $tag,
							"company_id" => $company->id,
							"token" => $token
						);

						$data_user = array(
							"name" => $request['name_new_user'],
							"username" => $request['new_username'],
							"email" => $request['email_new_user'],
							"password" => $request['password_new_user'],
							"phone" => $request['phone_new_user'],
							"company_id" => $company->id
						);

						//ASIGNAR UN PLAN A UNA COMPAÑÍA
						$company_plan = new CompanyPlan;
		            $company_plan->plan_id = $request['plan_id'];
		            $company_plan->automatic_payment = 1;
		            $company_plan->company_id = $companyid;
		            $company_plan->status_plan_id = 1;//Significa que está activo
		            $company_plan->save();

						//GUARDAR IP WHITELIST POR DEFECTO DEL CLIENTE
						$whitelist = new WhitelistCompany;
						$whitelist->ip_allow = $whitelist_ip;
						$whitelist->company_id = $company->id;
						$whitelist->save();

						//AQUI MANDO A CREAR LOS OBJETOS AL CHECKPOINT
						$object = $firewall->createObjectsCh($dataArray, $checkpoint, $ips_assigned);
						sleep(2);

						//AQUI MANDARÉ A CREAR LOS GROUPS
						$group = $network->createGroup($request['token']);

						Log::info($object);

						if($object == "success"){
							//Create section FOR RULES
							$section = $checkpoint->createSections($tag, $company->id);
							sleep(2);
							$section2 = $checkpoint2->createSections2($tag, $company->id);
							sleep(2);
							Log::info($section);

							if($section != "error"){

								$arrayRule = array(
									0 => array(
										'name' => "WHITELIST-INCOMING",
										'section' => $section[1],
										'source' => "CUST-".$tag."-WHITELIST-INCOMING",
										'destination' => "CUST-".$tag."-IP-ADDRESS",
										'vpn' => "Any",
										'action' => "Accept",
										'company_id' => $company->id,
										'tag' => $tag,
										'section_id' => $section[0]
									),
									1 => array(
										'name' => "WHITELIST-OUTGOING",
										'section' => $section[1],
										'source' => "CUST-".$tag."-IP-ADDRESS",
										'destination' => "CUST-".$tag."-WHITELIST-OUTGOING",
										'vpn' => "Any",
										'action' => "Accept",
										'company_id' => $company->id,
										'tag' => $tag,
										'section_id' => $section[0]
									),
									2 => array(
										'name' => "BLACKLIST-INCOMING",
										'section' => $section[1],
										'source' => "CUST-".$tag."-BLACKLIST-INCOMING",
										'destination' => "CUST-".$tag."-IP-ADDRESS",
										'vpn' => "Any",
										'action' => "Drop",
										'company_id' => $company->id,
										'tag' => $tag,
										'section_id' => $section[0]
									),
									3 => array(
										'name' => "BLACKLIST-OUTGOING",
										'section' => $section[1],
										'source' => "CUST-".$tag."-IP-ADDRESS",
										'destination' => "CUST-".$tag."-BLACKLIST-OUTGOING",
										'vpn' => "Any",
										"action" => "Drop",
										'company_id' => $company->id,
										'tag' => $tag,
										'section_id' => $section[0]
									)
								);

								$i = 0;
								$arr = [];

								foreach($arrayRule as $row){
									//Create rules
									sleep(3);
									$rules = $checkpoint->addRules($row);

									//Agrego las reglas en el checkpoint 118
									$rules2 = $checkpoint2->addRules2($row);

									Log::info($rules);
									Log::info("regla creada SIGUIENTE");
									Log::info($row);

									$arr[$i] = $rules;
									$i++;
								}

								if(!in_array("error", $arr)){
									//Log::info("Objetos creados exitosamente");
									#$install = $checkpoint->installPolicy();

									#if($install == "success"){
									sleep(3);
									$user = $this->createUserCompany($data_user);

									if($user == "success"){
										//Log::info("Se instalaron los cambios");
										if(isset($ips_assigned)){
							            foreach($ips_assigned as $value){
								      			$ip_initial_mk = $value['ip_init'];
								      			$ip_last_mk = $value['ip_last'];
							            }
						         	}else{
							            $ip_initial_mk = '1.1.1.1';
						     				$ip_last_mk = '1.1.1.1';
						         	}

										//AQUI VALIDARÉ SI SE CREARÁ USUARIO MIKROTIK
										if($user_mikrotik == true){
											//Obtengo el token del mitrotik
											$curl = curl_init();

											curl_setopt_array($curl, array(
									        	CURLOPT_URL => "http://172.16.3.35/MIkrotik/public/Sign?email=kr12%40red4g.net&password=123456",
									        	CURLOPT_RETURNTRANSFER => true,
									        	CURLOPT_ENCODING => "",
									        	CURLOPT_MAXREDIRS => 10,
									        	CURLOPT_TIMEOUT => 30,
									        	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
												CURLOPT_SSL_VERIFYPEER => false,
												CURLOPT_SSL_VERIFYHOST => false,
									        	CURLOPT_CUSTOMREQUEST => "POST",
									        	CURLOPT_HTTPHEADER => array(
									          	"cache-control: no-cache"
									        	),
									      ));

									      $response = curl_exec($curl);
									      $err = curl_error($curl);

									      curl_close($curl);

									      if ($err) {
									        	Log::info("cURL Error #:" . $err);
											  	$response_mk = 0;
									      }else{
							         		$result = json_decode($response, true);
								         	Log::info($result['token']);

												//AQUI MANDO A CREAR USER AL MIKROTIK
												$curl = curl_init();

												curl_setopt_array($curl, array(
											  		CURLOPT_URL => "http://172.16.3.35/MIkrotik/public/User?token=".$result['token'],
												  	CURLOPT_RETURNTRANSFER => true,
												  	CURLOPT_ENCODING => "",
												  	CURLOPT_MAXREDIRS => 10,
												  	CURLOPT_TIMEOUT => 30,
												  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
												  	CURLOPT_CUSTOMREQUEST => "POST",
												  	CURLOPT_POSTFIELDS => "username=".$tag_mk."&name=".$name."&ip=".$ip_initial_mk."&company_id=".$companyid."&group_id=1",
												  	CURLOPT_HTTPHEADER => array(
										    			"cache-control: no-cache",
												    	"content-type: application/x-www-form-urlencoded"
												  	),
												));

												$response = curl_exec($curl);
												$err = curl_error($curl);

												curl_close($curl);

												if ($err) {
											  		Log::info("cURL Error #:" . $err);
													$response_mk = 0;
												} else {
											  		$resultmk = json_decode($response, true);
													if(isset($resultmk['code']) && $resultmk['code'] == 200){
														Log::info($resultmk);
														$response_mk = 1;
													}else{
														Log::info($resultmk);
														$response_mk = 0;
													}
												}
								      	}
										}

										$data_email = array("name_company" => $name, "type_ssh" => "new_company");

										if(isset($response_mk) && ($response_mk == 1)){

											//Mando la instrucción para enviar el email anunciando la creación de objeto
				               		$emailCtrl->sendEmailSSHObj($data_email);

											return response()->json([
												'success' => [
													'tag_company' => $tag,
													'message' => 'Compañía, objetos y usuario creados exitosamente',
													'status_code' => 200
												]
											]);
										}else{
												return response()->json([
													'success' => [
														'tag_company' => $tag,
														'message' => 'Compañía, objetos y usuario creados exitosamente menos en Mikrotik',
														'status_code' => 200
													]
												]);
											}
									}else{
										return response()->json([
											'success' => [
												'message' => 'Se creó la compañía y objetos pero no el usuario',
												'status_code' => 200
											]
										]);
									}
								}else{
									return response()->json([
										'success' => [
											'message' => 'Se creó la compañía pero no los objetos',
											'status_code' => 200
										]
									]);
								}
							}else{
								return response()->json([
									'error' => [
										'message' => 'error al crear la sección para las reglas',
										'status_code' => 20
									]
								]);
							}
						}else{
							return response()->json([
								'success' => [
									'message' => 'Se creó la compañía pero no los objetos',
									'status_code' => 200
								]
							]);
						}
					}else{
						return response()->json([
							'error' => [
								'message' => 'Company not saved in bd',
								'status_code' => 20
							]
						]);
					}
				}else{
					Log::info("TAG NO CREADO");
					return response()->json([
		            'error' => [
		               'message' => 'Company not created',
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

	public function updateCompany(Request $request){

		$v = Validator::make($request->all(), [
			"name_company" => "required",
      	"address_company" => "required",
      	"email_company" => "required|email",
      	"phone_company" => "required",
    	]);

     	if($v->fails()){
			return response()->json([
          	'error' => [
	             'data' => $v->errors(),
	             'status_code' => 20
       		]
       	]);
    	}else{
			$id = $request['id'];
    		$name = $request['name_company'];
	    	$address = $request['address_company'];
	    	$email = $request['email_company'];
	    	$phone = $request['phone_company'];
	    	$description = isset($request['description_company']) ? $request['description_company'] : "";

	    	$company = Company::find($id);
	    	$company->name = $name;
			$company->address = $address;
			$company->email = $email;
			$company->phone = $phone;
			$company->description = $description;

			if($company->save()){
				return response()->json([
	            'success' => [
	               'company_id' => $company->id,
	               'message' => 'company updated',
	               'status_code' => 200
	            ]
	         ]);
			}else{
				return response()->json([
	            'error' => [
	               'message' => 'company not updated',
	               'status_code' => 20
	            ]
	         ]);
			}
		}
	}

	public function createUserCompany($data){

		$name_sep = explode(' ', $data['name']);
		$role_id_user = 2; //Administrador

		try{
			$user = new User;
			$user->name = $name_sep[0];
			$user->lastname = $name_sep[1];
			$user->username = $data['username'];
			$user->email = $data['email'];
			$user->password = Hash::make($data['password']);
			$user->phone = $data['phone'];
			$user->company_id = $data['company_id'];
			$user->active = 1;
			$user->api_token = str_random(40);//api token
			$user->save();

			if($user->id){
				$id = DB::table('role_user')->insertGetId(
					['user_id' => $user->id, 'role_id' => $role_id_user]
				);
			}

			return "success";
		}catch(Exception $e){
			// do task when error
			Log::info($e->getMessage());

			return "error";
		}
	}

	public function validateCompany(){

		$company = Company::count();
		$users = User::count();

		return response()->json([
    		"company" => $company,
				"user" => $users
		]);
	}

	public function createFirstCompany(Request $request){
		$name = $request['name_company'];
		$address = $request['address_company'];
		$email = $request['email_company'];
		$phone = $request['phone_company'];
		$description = isset($request['description_company']) ? $request['description_company'] : "";

		$words = explode(" ", $name);
		$acronym = "";

		foreach ($words as $w) {
		  	$acronym .= $w[0];
		}

		$random = rand(100, 999);
		$account = '000'.$random;
		$tag = $acronym.''.$random;
		$country_id = $request['country_id'];

		/*Mandaré a guardar el tag cuando se crea la compañía*/
		try{
			$client = new \GuzzleHttp\Client();
			$data = $client->post("https://172.16.3.150/api?key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09&type=config&action=set&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/tag/entry[@name='".$tag."']&element=<comments>Tag de la compañía: ".$name."</comments>", ["verify" => false]);
			$response = $data->getBody()->getContents();

			$xml = new \SimpleXMLElement($response);
			$code = $xml['code'];

			$tagCheck = $checkpoint->createTag($tag);
			sleep(3);

			if(($code == 19 || $code == 20) && $tagCheck == "success"){
				$company = new Company;
				$company->name = $name;
				$company->address = $address;
				$company->email = $email;
				$company->phone = $phone;
				$company->description = $description;
				$company->account = $account;
				$company->tag = $tag;
				$company->country_id = $country_id;
				$company->save();

			}else{
				Log::info("TAG NO CREADO");
				return response()->json([
					'error' => [
						'message' => 'Company not created',
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

	public function destroy(Request $request, CheckpointController $checkpoint, EmailController $emailCtrl){

		$id = $request['company_id'];
		$name = $request['company_name'];

		$rules = FwAccessRule::where('company_id', '=', $id)->get();
		$objects = FwObject::where('company_id', '=', $id)->get();
		$sections = FwSectionAccess::where('company_id', '=', $id)->get();

		$remove_rules = $checkpoint->removeRuleComplete($rules);
		$remove_objects = $checkpoint->removeObjectComplete($objects);
		$remove_section = $checkpoint->removeSectionComplete($sections);

		$company = Company::find($id);
		$company->delete();

		if($company){
			return response()->json([
          'success' => [
             'message' => 'Datos de compañía eliminados',
             'status_code' => 200
          ]
       ]);
		}else{
			$type = "error";
			$update_rule = Company::where('id', $id)
				->update(['status_error' => 1]);

			$emailCtrl->sendEmailCompany($name, $type);

			return response()->json([
	          'error' => [
	             'message' => 'Compañía no pudo ser eliminada',
	             'status_code' => 20
	          ]
	       ]);
		 }
 	}

	public function getIpsBlocked(Request $request){

		$blocked = BlockedIp::get();

		return response()->json([
			'data' => $blocked
		]);
	}

	public function deleteIpBlocked(Request $request){

		$id_block = $request['id'];

		$delete = BlockedIp::find($id_block);
		$delete->delete();

		if($delete){
			return response()->json([
            'success' => [
               'message' => 'IP y Usuario desbloqueado',
               'status_code' => 200
            ]
         ]);
		}else{
			return response()->json([
            'error' => [
               'message' => 'No se pudo desbloquear el usuario',
               'status_code' => 20
            ]
         ]);
		 }
	}

}
