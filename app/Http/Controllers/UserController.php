<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Illuminate\Support\Collection;
use Datatables;

use App\User;
use Hash;

use JWTAuth;

class UserController extends Controller
{
	use EntrustUserTrait;

	public function updateInformation(Request $request){

		$user_id = $request['user_id'];

		$v = Validator::make($request->all(), [
			'name_user' => 'required',
			'username' => 'required',
			'email_user' => 'required|email',
			'phone_user' => 'required|numeric',
			'password_user' => 'required|min:4',
		]);

		if($v->fails()){
		 	// return redirect()->back()->withErrors($v->errors());
		 	return response()->json([
		    	'error' => [
		       		'data' => $v->errors(),
		       		'status_code' => 20
		    	]
	 		]);
		}else{

		 	$name_sep = explode(' ', $request['name_user']);
		 	$role_user = $request['role_user'];

		 	try{
		    	$user = User::find($user_id);
		    	#$user->first_name = (isset($name_sep[0])) ? $name_sep[0] : '';
		    	$user->name = $name_sep[0];
		    	#$user->last_name = (isset($name_sep[1])) ? $name_sep[1] : '';
		    	$user->lastname = $name_sep[1];
		    	$user->username = $request['username'];
		    	$user->email = $request['email_user'];
	    		$user->password = Hash::make($request['password_user']);
		    	$user->phone = $request['phone_user'];
		    	$user->company_id = 1;
				$user->active = 1;
				$user->api_token = str_random(40); //our api token
		    	$user->save();

		    	if($user->id){

					$role = DB::table('role_user')
		          	->where('user_id', $user_id)
		          	->update(['role_id' => $role_user]);

			       	return response()->json([
			          	'success' => [
			             	'api_token' => $user->api_token,
			             	'message' => 'User modified successful',
			             	'status_code' => 200
			          	]
			       	]);
		    	}else{
		       	return response()->json([
						'error' => [
							'message' => 'User not created',
							'status_code' => 20
						]
					]);
		    	}

	 		}catch(Exception $e){
		    	// do task when error
		    	Log::info($e->getMessage());
		    	return response()->json([
					'error' => [
						'message' => $e->getMessage(),
						'status_code' => 20
					]
				]);
	 		}
		}
	}

	public function getDataUsers(Request $request){

		$userLog = JWTAuth::toUser($request['token']);
		$role_user = $userLog->roles->first()->name;

		if($role_user == "superadmin"){
			$users = DB::table('users')
			->join('fw_companies', 'users.company_id', '=', 'fw_companies.id')
			->join('role_user', 'users.id', '=', 'role_user.user_id')
			->join('roles', 'role_user.role_id', '=', 'roles.id')
			->select('users.id AS id', 'users.name AS uname', 'users.lastname AS ulastname', 'users.username', 'users.email', 'users.phone', 'fw_companies.name AS company', 'roles.display_name AS role')
			->where('users.deleted_at', NULL)
			->where('fw_companies.deleted_at', NULL)
			->get();

		}else{
			$users = DB::table('users')
			->join('fw_companies', 'users.company_id', '=', 'fw_companies.id')
			->join('role_user', 'users.id', '=', 'role_user.user_id')
			->join('roles', 'role_user.role_id', '=', 'roles.id')
			->select('users.id AS id', 'users.name AS uname', 'users.lastname AS ulastname', 'users.username', 'users.email', 'users.phone', 'fw_companies.name AS company', 'roles.display_name AS role')
			->where('users.deleted_at', NULL)
			->where('fw_companies.deleted_at', NULL)
			->where('users.company_id', $userLog['company_id'])
			->get();
		}

		$array_user = [];
		$i = 0;

		$users_data = json_encode($users);
		$users_data2 = json_decode($users_data, true);

		foreach ($users_data2 as $key => $value) {
			$array_user[$i]['id'] = $value['id'];
			$array_user[$i]['name'] = $value['uname'].' '.$value['ulastname'];
			$array_user[$i]['username'] = $value['username'];
			$array_user[$i]['email'] = $value['email'];
			$array_user[$i]['phone'] = $value['phone'];
			$array_user[$i]['company'] = $value['company'];
			$array_user[$i]['role'] = $value['role'];

		 	$i++;
		}

		return response()->json([
			'success' => [
				'data' => $array_user,
				'status_code' => 200
			]
	 	]);
	}

	public function addUser(Request $request){

		$v = Validator::make($request->all(), [
		 	"name_new_user" => "required",
		 	"username_new_user" => "required",
		 	"email_new_user" => "required|email",
		 	"phone_new_user" => "required|numeric",
		 	"password_new_user" => "required|min:4",
		]);

	  	if($v->fails()){
	     	// return redirect()->back()->withErrors($v->errors());
	     	return response()->json([
	        	'error' => [
           		'data' => $v->errors(),
	           	'status_code' => 20
	        	]
	     	]);
	  	}else{
	     	$name_sep = explode(' ', $request['name_new_user']);

	     	if(Session::has("company_tag")){
            $tag = Session::get("company_tag");
            $company_data = DB::table("fw_companies")->where("tag", "=", $tag)->get();
            $company_data = json_encode($company_data);
            $company_data2 = json_decode($company_data, true);
            $company_id = $company_data2[0]['id'];
		 	}else{
            $company_id = 2;
		 	}

	     	try{
            $user = new User;
            $user->name = $name_sep[0];
            $user->lastname = $name_sep[1];
            $user->username = $request['username_new_user'];
            $user->email = $request['email_new_user'];
            $user->password = Hash::make($request['password_new_user']);
            $user->phone = $request['phone_new_user'];
            $user->company_id = $company_id;
            $user->active = 1;
            $user->api_token = str_random(40);//api token
            $user->save();

            if($user->id){
               $id = DB::table('role_user')->insertGetId(
                  ['user_id' => $user->id, 'role_id' => $request['role_new_user']]
               );
            }

            Session::flash("user_success", trans('user.user_created'));

	     	}catch(Exception $e){
            // do task when error
            Log::info($e->getMessage());
            Session::flash("errorUser", trans('user.user_not_created'));
	     	}
         	return redirect("user/user_index");
      	}
   	}

   	public function destroy(Request $request){
      	$id = $request['id_user'];
      	$user = User::find($id);
      	$user->delete();

      	if($user){

         	DB::table('role_user')->where('user_id', $id)->delete();
         	return response()->json([
            	"message" => 1
         	]);
      	}else{
         	return response()->json([
            	"message" => 0
         	]);
      	}
   	}

   	public function getNameUser($id){

      	$user = User::select('name','lastname')->where('id', $id)->get();

      	foreach($user as $row){
         	$name = $row['name'].' '.$row['lastname'];
      	}

      	return response()->json([
         	"name" => $name
      	]);
   	}

   	public function getRolesData(Request $request){

      	$user = JWTAuth::toUser($request['token']);
      	$role_user = $user->roles->first()->name;

      	if($role_user == "superadmin"){
         	$roles = DB::table("roles")
            ->select('id', 'display_name AS text', 'description')->get();
      	}else{
         	$roles = DB::table("roles")
            ->where('name', '!=', "superadmin")
            ->select('id', 'display_name AS text', 'description')->get();
      	}

      	return response()->json($roles);
   	}

   	public function verifyToken(Request $request){

      	try {
   			if(! $user = JWTAuth::parseToken()->authenticate()) {
		      	return response()->json(['user_not_found'], 404);
   			}
   		}catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
   			return response()->json(['token_expired'], $e->getStatusCode());
   		}catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
   			return response()->json(['token_invalid'], $e->getStatusCode());
   		}catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
   			return response()->json(['token_absent'], $e->getStatusCode());
   		}

			if(!empty($user['deleted_at'])){
				return response()->json(['user_deleted']);
			}else{
				// the token is valid and we have found the user via the sub claim
				return response()->json(compact('user'));
			}

   	}
}
