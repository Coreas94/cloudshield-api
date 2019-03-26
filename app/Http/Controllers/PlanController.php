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
use App\ServicesPlans;

class PlanController extends Controller{

   public function getPlans(Request $request){

      $plans = Plans::all();


      return response()->json([
         'plans' => $plans,
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

      $name = $request['name'];
      $description = $request['description'];
      $price = $request['price'];
      $duration = $request['duration_plan'];
      $company_id = $request['company_id'];

      $plan = new Plans;
      $plan->name = $name;
      $plan->description = $description;
      $plan->price = $price;
      $plan->duration = $duration;
      $plan->save();

      if($plan->id){

         if(isset($request['company_id'])){
            //Asigno el plan a una compañía en especifico
            $company_plan = new CompanyPlan;
            $company_plan->plan_id = $plan->id;
            $company_plan->automatic_payment = 1;
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

      $plan_id = $request['plan_id'];
      $name = $request['name'];
      $description = $request['description'];
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

      $plan = Company::find($id);
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

}
