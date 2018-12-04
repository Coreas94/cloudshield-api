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
use Datatables;
use App\Company;
use App\User;
use App\FwAccessRule;
use Hash;
use App\FwSectionAccess;
use App\FwObject;
use App\Http\Controllers\CheckPointFunctionController;

use JWTAuth;

class AccessController extends Controller{


	public function getDataCompanies(Request $request){

		$userLog = JWTAuth::toUser($request['token']);
	  	$role_user = $userLog->roles->first()->name;

     	if($role_user == "superadmin"){
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

		return response()->json($arreglo);
	}

	public function addCompany(Request $request, CheckpointController $checkpoint, FireWallController $firewall){

		$checkpoint2 = new CheckPointFunctionController;

		$v = Validator::make($request->all(), [
   		"name_company" => "required",
      	"address_company" => "required",
      	"email_company" => "required|email",
			"phone_company" => "required|numeric",

			"name_new_user" => "required",
			"username_new_user" => "required",
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

	    	$name = $request['name_company'];
	    	$address = $request['address_company'];
	    	$email = $request['email_company'];
	    	$phone = $request['phone_company'];
	    	$description = isset($request['description_company']) ? $request['description_company'] : "";
			$token = $request['token'];

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
					$company->save();

					if($company->id){
						$dataArray = array(
							"name" => $name,
							"tag" => $tag,
							"company_id" => $company->id,
							"token" => $token
						);

						$data_user = array(
							"name" => $request['name_new_user'],
							"username" => $request['username_new_user'],
							"email" => $request['email_new_user'],
							"password" => $request['password_new_user'],
							"phone" => $request['phone_new_user'],
							"company_id" => $company->id
						);

						$object = $firewall->createObjectsCh($dataArray, $checkpoint);
						sleep(3);
						Log::info($object);

						if($object == "success"){
							//Create section
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
												'message' => 'Se creó la compañía y objetos pero no el usuario',
												'status_code' => 200
											]
										]);
									}
									/*}else{
										return response()->json([
											'error' => [
												'message' => 'Datos ingresados correctamente, pero no fueron instalados en checkpoint',
												'status_code' => 20
											]
										]);
									}*/
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
			$id = $request['company_id'];
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
			'user' => $users
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

}
