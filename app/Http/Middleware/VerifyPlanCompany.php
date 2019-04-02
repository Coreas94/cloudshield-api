<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use App\Company;
use App\CompanyPlan;
use JWTAuth;

class VerifyPlanCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
     public function handle($request, Closure $next){
        //Log::info($request['token']);
        //return $next($request);
        $user = JWTAuth::toUser($request['token']);
        $company_id = $user['company_id'];

        $plan = CompanyPlan::where('company_id', '=', $company_id)->get();

         if(count($plan) > 0){
            foreach($plan as $row){
               $status = $row['status_plan_id'];
            }

            if($status == 1){
               return $next($request);
            }else{
               //return redirect('/')->with('error','You have not admin access');
               return response()->json([
   					'error' => [
   						'message' => 'El plan está suspendido o deshabilitado',
   						'status_code' => 20
   					]
   				]);
            }
         }else{
            //return redirect('/')->with('error','You have not admin access');
            return response()->json([
					'error' => [
						'message' => 'La empresa no pertenece a ningún plan',
						'status_code' => 20
					]
				]);
         }
    }
}
