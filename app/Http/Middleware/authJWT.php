<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;

class authJWT
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next){
		try {
				JWTAuth::toUser($request->input('token'));
		}catch (Exception $e) {
				if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
						return response()->json(['error'=>'Token is Invalid', 'message' => $e->getMessage()]);
				}else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
						return response()->json(['error'=>'Token is Expired', 'message' => $e->getMessage()]);
				}else{
						return response()->json(['error'=>'Error with token', 'message' => 'null']);
				}
		}

		return $next($request);
	}
}
