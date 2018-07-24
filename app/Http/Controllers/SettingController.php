<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Collection; // to generate collections.
use Datatables;

use App\Company;

use JWTAuth;


class SettingController extends Controller{

	public function __construct(){
		//$this->middleware('auth');
	}

	public function index(){
		return view('settings.index');
	}

	public function orderPoliciesPA(){

		try {

			$userLog = JWTAuth::toUser($request['token']);
			$company_id = $userLog['company_id'];

	    	$client = new \GuzzleHttp\Client();
			$data = $client->post("https://172.16.3.150/api?type=config&action=get&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/rulebase/security&key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09", ["verify" => false]);
			$response = $data->getBody()->getContents();

			$xml = new \SimpleXMLElement($response);
			$code = $xml['code'];

			if ($code == 19 || $code == 20) {

				$company = DB::table('fw_companies')->orderBy('name', 'asc')->pluck('name', 'tag');

				$result = $xml->result;
				$rules = $result->security->rules;

				$i=0;
				$entry = [];
				$name_old = '';

				$poli = DB::table('fw_policies_security')
						->join('fw_action_detail', 'fw_policies_security.action_id', '=', 'fw_action_detail.id')
						->where('fw_policies_security.status', 1)
						->select('fw_policies_security.name', 'fw_policies_security.action_id', 'fw_action_detail.action')
						->get();

				//Creo el arreglo que contiene todas las politicas de seguridad
				foreach ($rules->entry as $item) {

					$dataAux = json_encode($item);
					$dataAux2 = json_decode($dataAux, true);

					$name = json_encode($item['name']);
					$tag = $dataAux2['tag']['member'];

					$entry[$i]['name'] = json_decode($name, true);
					$entry[$i]['tag'] = $tag;

					$i++;
				}

				$c = 0;
				foreach ($company as $key => $value) {
					foreach ($entry as $row) {
						if($key == $row['tag']){
							if(empty($name_old)){
								$typeMov = 'top';
								$xml = $typeMov;
							}else{
								$typeMov = 'after&dst';
								$xml = $typeMov."=".$name_old;
							}

							try {
						    	$client = new \GuzzleHttp\Client();
								$data = $client->post("https://172.16.3.150/api?type=config&action=move&key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09&xpath=/config/devices/entry/vsys/entry/rulebase/security/rules/entry[@name='".$row['name'][0]."']&where=".$xml, ["verify" => false]);

								$response = $data->getBody()->getContents();

								$xml = new \SimpleXMLElement($response);
								$code = $xml['code'];

								if ($code == 19 || $code == 20) {
									$name_old = $row['name'][0];
								}
								elseif ($code == 14) {
									$data = $client->post("https://172.16.3.150/api?type=config&action=move&key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09&xpath=/config/devices/entry/vsys/entry/rulebase/security/rules/entry[@name='".$row['name'][0]."']&where=after&dst=".$row['name'][0], ["verify" => false]);
									$name_old = $row['name'][0];
								}
								else {
									Log::info(print_r($response, true));
									Log::info("Falló la politica: ".$row['name'][0]);
								}
							}catch (GuzzleHttp\Exception\ClientException $e) {
							    $response = $e->getResponse();
							    $responseBodyAsString = $response->getBody()->getContents();
							}
						}
						$c++;
					}
				}

      			return redirect('policies/index');
			} else {
			  	Log::info($response);
			  	return view('welcome')->with(compact($err));
			}
		}
		catch (GuzzleHttp\Exception\ClientException $e) {
		   	$response = $e->getResponse();
		   	$responseBodyAsString = $response->getBody()->getContents();
		}
	}

	public function orderObjects(){

		try {
	    	$client = new \GuzzleHttp\Client();

			$data = $client->post("https://172.16.3.150/api/?type=config&action=get&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/address&key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09", ["verify" => false]);
			$data2 = $client->post("https://172.16.3.150/api/?type=config&action=get&xpath=/config/devices/entry[@name='localhost.localdomain']/vsys/entry[@name='vsys1']/address-group&key=LUFRPT00eHE5UExyWEIrbnY3eEZ0SmRXMGVhcForVmc9bHphSCs4VGFSMk5QOS9CQnJkK1R1QT09", ["verify" => false]);

			$response = $data->getBody()->getContents();
			$response2 = $data2->getBody()->getContents();

			$xml = new \SimpleXMLElement($response);
			$xml2 = new \SimpleXMLElement($response2);

			$code  = $xml['code'];
			$code2 = $xml2['code'];

			$data  = [];
			$data2 = [];

			if ($code == 19 || $code == 20) {
				$result = $xml->result;

				$i = 0;
				foreach ($result->address->entry as $item) {
					$name = json_encode($item['name']);
					$data[$i]['name'] = json_decode($name, true);

					$i++;
				}
			}

			if ($code2 == 19 || $code2 == 20) {
				$result2 = $xml2->result;

				$i = 0;
				foreach ($result2->{'address-group'}->entry as $item) {
					$name = json_encode($item['name']);
					$data2[$i]['name'] = json_decode($name, true);

					$i++;
				}
			}
		}catch (GuzzleHttp\Exception\ClientException $e) {
		   	$response = $e->getResponse();
		   	$responseBodyAsString = $response->getBody()->getContents();
		}
	}

	public function getCountriesData(){

		$countries = DB::table('countries')->select('id', 'name AS text', 'code', 'abbreviation')->get();

		return response()->json([
			#'success' => [
				'status_code' => 200,
				'data' => $countries
			#]
		]);
	}


}
