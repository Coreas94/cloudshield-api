<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Validator;
use App\Http\Controllers\Controller;
use App\BlockedIp;
use App\WhitelistCompany;

use Hash;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;

use JWTAuth;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller
{
   /*
   |--------------------------------------------------------------------------
   | Registration & Login Controller
   |--------------------------------------------------------------------------
   |
   | This controller handles the registration of new users, as well as the
   | authentication of existing users. By default, this controller uses
   | a simple trait to add these behaviors. Why don't you explore it?
   |
   */

   use AuthenticatesAndRegistersUsers, ThrottlesLogins;

   /**
   * Where to redirect users after login / registration.
   *
   * @var string
   */
   protected $redirectTo = '/';
   protected $maxLoginAttempts = 3; // Amount of bad attempts user can make
   protected $lockoutTime = 60; // Time for which user is going to be blocked in seconds

   /**
   * Create a new authentication controller instance.
   *
   * @return void
   */
   public function __construct()
   {
      //$this->limiter = $limiter;
      $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
   }

   /**
   * Get a validator for an incoming registration request.
   *
   * @param  array  $data
   * @return \Illuminate\Contracts\Validation\Validator
   */
   protected function validator(array $data)
   {
      return Validator::make($data, [
         'name' => 'required|max:255',
         'email' => 'required|email|max:255|unique:users',
         'password' => 'required|min:6|confirmed',
      ]);
   }

   /**
   * Create a new user instance after a valid registration.
   *
   * @param  array  $data
   * @return User
   */
   protected function create(array $data){
      return User::create([
         'name' => $data['name'],
         'email' => $data['email'],
         'password' => bcrypt($data['password']),
      ]);
   }

   public function blockAttemps(Request $request){
      $email = $request['email'];
      $num_attemps = $request['num_attemps'];
      $last_attemp = $request['last_attemp'];
      $ip = \request()->ip();

      $block = new BlockedIp;
      $block->email = $email;
      $block->ip_address = $ip;
      $block->num_attemps = $num_attemps;
      $block->last_attemp = $last_attemp;
      $block->save();

      if($block){
         return response()->json([
     			'success' => [
     				'message' => 'OK',
     				'status_code' => 200
     			]
     		]);
      }else{
         return response()->json([
  				'error' => [
  					'message' => 'Not OK',
  					'status_code' => 20
  				]
  			]);
      }
   }

   public function api_login(Request $request){

      $ips_allow = WhitelistCompany::pluck('ip_allow')->toArray();
      $ip = \request()->ip();

      if(in_array($ip, $ips_allow)){

         $validator = Validator::make($request->all(), ['email' => 'required', 'password' => 'required']);
     		$input = $request->all();

         $blocked = BlockedIp::where('email', '=', $input['email'])->orWhere('ip_address', '=', $ip)->get()->toArray();

         if(count($blocked) > 0){
            return response()->json([
               'error' => [
                  'message' => 'User blocked, please contact the administrator.',
                  'status_code' => 5
               ]
            ]);
         }else{
            if(!$token = JWTAuth::attempt($input)) {
        			// return response()->json(['result' => 'wrong email or password.']);
        			return response()->json([
        				'error' => [
        					'message' => 'Login failed',
        					'status_code' => 20
        				]
        			]);
        		}

        		$user = JWTAuth::toUser($token);
        		$role_user = $user->roles->first()->name;

        		return response()->json([
        			'success' => [
        				'api_token' => $token,
        				'role_user' => $role_user,
        				'message' => 'Login successful',
        				'status_code' => 200
        			]
        		]);
         }
      }else{
         return response()->json([
            'error' => [
               'message' => 'Unauthorized IP',
               'status_code' => 20
            ]
         ]);
      }
	}

   public function api_signup(Request $request){

		Log::info("llega al controller");
      Log::info($request);

		$v = Validator::make($request->all(), [
			'name_new_user' => 'required',
			"username_new_user" => "required",
			"email_new_user" => "required|email",
			"phone_new_user" => "required|numeric",
			"password_new_user" => "required|min:4",
		]);

		if($v->fails()){
			return $v->errors();
		}else{

			try{

				$name_sep = explode(' ', $request['name_new_user']);

				$user = new User;
				$user->name = $name_sep[0];
            $user->lastname = $name_sep[1];
				$user->username = $request['username_new_user'];
				$user->email = $request['email_new_user'];
				$user->password = Hash::make($request['password_new_user']);
				$user->phone = $request['phone_new_user'];
				$user->company_id = $request['user_company_id'];
				$user->active = 1;
				$user->api_token = str_random(40); //our api token
				$user->save();

				if($user->api_token){

					if($user->id){
	               $id = DB::table('role_user')->insertGetId(
	                  ['user_id' => $user->id, 'role_id' => $request['role_new_user']]
	               );
	            }

					return response()->json([
						'success' => [
							'api_token' => $user->api_token,
							'message' => 'User created successful',
							'status_code' => 200
						]
					]);
				}
				//Session::flash("user_success", "¡User created successfully!");
			}catch(Exception $e){
				// do task when error
				Log::info($e->getMessage());
				Session::flash("errorUser", "¡Error, User not created!");
				return response()->json([
					'error' => [
						'message' => 'User not created',
						'status_code' => 20
					]
				]);
			}
			//return redirect("user/user_index");
		}
	}
}
