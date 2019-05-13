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

use App\Plans;
use App\CompanyPlan;
use App\Company;
use App\ServicesPlans;
use App\DetailPlan;
use Carbon\Carbon;

class PlanController extends Controller{

   public function getPlans(Request $request){

      $planes = [];
      $services = [];
      $all = [];

      $plans = Plans::all();

      foreach($plans as $val){

         $planes['id'] = $val['id'];
         $planes['name'] = $val['name'];
         $planes['description'] = $val['description'];
         $planes['price'] = $val['price'];
         $planes['duration'] = $val['duration'];
         $planes['editable'] = $val['editable'];
         $planes['created_at'] = $val['created_at']->toDateTimeString();
         $planes['updated_at'] = $val['updated_at']->toDateTimeString();

         $services_all = ServicesPlans::join('detail_plan', 'services_plans.id', '=', 'detail_plan.id_service')
            ->where('detail_plan.plan_id', '=', $val['id'])
            ->select('services_plans.id as id_service', 'services_plans.name_service', 'detail_plan.plan_id')
            ->get();

         $planes['services'] = $services_all;

         array_push($all, $planes);
      }

      return response()->json([
         'plans' => $all,
         'status_code' => 200
      ]);
   }

   public function getServices(){
      $services = ServicesPlans::all();

      return response()->json([
         'services' => $services,
         'status_code' => 200
      ]);
   }

   public function createPlan(Request $request){
      Log::info($request);

      $name = $request['name'];
      $description = $request['description'];
      $price = $request['price'];
      $duration = $request['duration_plan'];
      $dataServices = [];
      $current_time = Carbon::now()->toDateTimeString();

      $plan = new Plans;
      $plan->name = $name;
      $plan->description = $description;
      $plan->price = $price;
      $plan->duration = $duration;
      $plan->save();

      if($plan->id){

         if(isset($request['services'])){
            foreach($request['services'] as $row){
               $dataServices[] = [
                  'id_service' => $row['id'],
                  'plan_id' => $plan->id,
                  'created_at' => $current_time,
                  'updated_at' => $current_time
               ];
            }

            $srv = DetailPlan::insert($dataServices);
         }

         if(isset($request['company_id'])){
            $dt = \Carbon\Carbon::now();
            $company_id = $request['company_id'];

            if($duration == "yearly"){
               $expiration = $dt->addYear();
            }else{
               $expiration = $dt->addMonth();
            }

            //Asigno el plan a una compañía en especifico
            $company_plan = new CompanyPlan;
            $company_plan->plan_id = $plan->id;
            $company_plan->automatic_payment = 1;
            $company_plan->company_id = $company_id;
            $company_plan->expiration_date = $expiration;
            $company_plan->status_plan_id = 1; //Significa que está activo
            $company_plan->save();

            if($company_plan->id){
               return response()->json([
                  'success' => [
                     'data' => "Plan agregado con éxito",
                     'status_code' => 200
                  ]
               ]);
            }else{
               return response()->json([
                  'success' => [
                     'data' => "Plan creado pero no pudo asignarse a la compañía.",
                     'status_code' => 200
                  ]
               ]);
            }
         }else{
            return response()->json([
               'success' => [
                  'data' => "Plan creado pero no se asignó a una compañía.",
                  'status_code' => 200
               ]
            ]);
         }
      }else{
         return response()->json([
            'error' => [
               'message' => 'El plan no pudo ser agregado.',
               'status_code' => 20
            ]
         ]);
      }
   }

   public function editPlan(Request $request){
      Log::info($request);

      $plan_id = $request['plan_id'];
      $name = $request['name'];
      $description = isset($request['description']) ? $request['description'] : "Null";
      $price = $request['price'];
      $duration = $request['duration_plan'];

      $plan = Plans::find($plan_id);
      $plan->description = $description;
      $plan->price = $price;
      $plan->duration = $duration;

      if($plan->save()){
         return response()->json([
            'success' => [
               'plan_id' => $plan->id,
               'message' => 'El plan se actualizó correctamente.',
               'status_code' => 200
            ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => 'No se pudo actualizar el plan.',
               'status_code' => 20
            ]
         ]);
      }
   }

   public function removePlan(Request $request){

      $plan_id = $request['plan_id'];

      $plan = Plans::find($id);
		$plan->delete();

		if($plan){
			return response()->json([
             'success' => [
                'message' => 'Plan eliminado correctamente.',
                'status_code' => 200
             ]
         ]);
		}else{
			return response()->json([
	          'error' => [
	             'message' => 'El plan no pudo ser eliminado.',
	             'status_code' => 20
	          ]
	       ]);
		 }
   }

   public function assignPlanCompany($company_id, $plan_id, $automatic_payment, $duration){

      $dt = \Carbon\Carbon::now();
      // $company_id = $request['company_id'];
      // $plan_id = $request['plan_id'];

      if($duration == "yearly"){
         $date_exp = $dt->addYear();
         $expiration = $date_exp->toDateString();
      }else{
         $date_exp = $dt->addMonth();
         $expiration = $date_exp->toDateString();
      }

      //Asigno el plan a una compañía en especifico
      $company_plan = new CompanyPlan;
      $company_plan->plan_id = $plan_id;
      $company_plan->automatic_payment = $automatic_payment;
      $company_plan->company_id = $company_id;
      $company_plan->expiration_date = $expiration;
      $company_plan->status_plan_id = 1; //Significa que está activo
      $company_plan->save();

      if($company_plan->id){
         return "success";
      }else{
         return "error";
      }
   }

   public function assignManualPlanCompany(Request $request){

      $dt = \Carbon\Carbon::now();
      $company_id = $request['company_id'];
      $plan_id = $request['plan_id'];
      $automatic_payment = $request['payment_automatic'];
      $duration = $request['duration'];

      if($duration == "yearly"){
         $date_exp = $dt->addYear();
         $expiration = $date_exp->toDateString();
      }else{
         $date_exp = $dt->addMonth();
         $expiration = $date_exp->toDateString();
      }

      $exist = CompanyPlan::where('company_id', '=', $company_id)->count();

      if($exist == 0){
         //Asigno el plan a una compañía en especifico
         $company_plan = new CompanyPlan;
         $company_plan->plan_id = $plan_id;
         $company_plan->automatic_payment = $automatic_payment;
         $company_plan->company_id = $company_id;
         $company_plan->expiration_date = $expiration;
         $company_plan->status_plan_id = 1; //Significa que está activo
         $company_plan->save();

      }else{
         $company_plan = DB::table('company_plan')
            ->where('company_id', '=', $company_id)
            ->update(['plan_id' => $plan_id, 'expiration_date' => $expiration]);
      }

      if($company_plan){
         return response()->json([
             'success' => [
                'message' => 'Plan asignado correctamente.',
                'status_code' => 200
             ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => 'El plan no pudo ser asignado.',
               'status_code' => 20
            ]
         ]);
      }
   }

   public function changePlanCompany(Request $request){

      $old_plan_id = $request['old_plan_id'];
      $new_plan_id = $request['new_plan_id'];
      $company_id = $request['company_id'];

      $plan = DB::table('company_plan')
         ->where('company_id', '=', $company_id)
         ->update(['plan_id' => $new_plan_id]);

      if($plan){
         return response()->json([
             'success' => [
                'message' => 'Plan actualizado correctamente.',
                'status_code' => 200
             ]
         ]);
      }else{
         return response()->json([
            'error' => [
               'message' => 'El plan no pudo ser actualizado.',
               'status_code' => 20
            ]
         ]);
      }
   }

}
