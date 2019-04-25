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
use Illuminate\Support\Facades\Crypt;

use App\Plans;
use App\CompanyPlan;
use App\CustomerPayment;
use App\Invoice;
use JWTAuth;

class PaymentController extends Controller{

   function func_x1($n){
      return sqrt(($n - 4) / 4);
   }

   function func_x2($n){
      return sqrt($n - 10);
   }

   function func_x3($n){
      return 2 - $n;
   }

   function hiddenString($str){
      $start = 0;
      $end = 4;
      $len = strlen($str);
      return substr($str, 0, $start) . str_repeat('*', $len - ($start + $end)) . substr($str, $len - $end, $end);
   }

   public function decoder($data){

      $content = base64_decode(explode('@', str_replace('#', '', $data))[1]);
      $normalized = [[1,3,5,7],[9,11,13,15],[2,4,6,8],[10,12,14,16]];
      $blob = json_decode($content);

      $nOrder = [];
      $toDivide = [];
      $order = [];
      $decode = [];
      $code = $blob->crd;
      $sc = $blob->cv;
      $ed = $blob->ed;
      $tc = $blob->tc;
      $blobCode = json_decode(base64_decode($code));

      foreach ($blobCode as $key_0 => $value_0) {
        $toDivide = $normalized[$key_0];
        foreach ($value_0 as $key_1 => $value_1) {
            $num = $value_1 / $toDivide[$key_1];
            array_push($order, array(
                'n' => $num,
                'i' => $toDivide[$key_1]
            ));
         }
      }

      for($i = 0; $i < 16; $i++){
        foreach ($order as $key => $value) {
            $index = $i + 1;
            if($value['i'] == $index){
                if($value['i'] % 2 != 0) array_push($nOrder, $this->func_x1($value['n']));
                else array_push($nOrder, $this->func_x2($value['n']));
            }
         }
      }

      $val_0 = join(array_slice($nOrder, 0, 4));
      $val_1 = join(array_slice($nOrder, 4, 4));
      $val_2 = join(array_slice($nOrder, 8, 4));
      $val_3 = join(array_slice($nOrder, 12, 4));

      $card_number = $val_0.$val_1.$val_2.$val_3;
      $ccv = $this->func_x3($sc);
      $edate = base64_decode($ed);
      $tcard = base64_decode($tc);

      $response2 =$this->hiddenString($card_number);
      $resp = [];
      $resp = array(
         "secure_code" => $ccv,
         "credit_exp" => $edate,
         "card_brand" => $tcard
      );

      return $resp;

      print "${response2} <br>";   // ORIGINAL CREDIT CARD
      print "${ccv} <br>";        // CCV
      print "${edate} <br>";      // EXPIRATION DATE
      print "${tcard} <br>";      // TYPE CARD
   }

   public function getAutomaticPayment(){
      Log::info("llega al pago automatico cron");
      $dt = \Carbon\Carbon::now();
      $now = $dt->toDateString();

      $data_payment = CompanyPlan::where('automatic_payment', '=', 1)->where('expiration_date', '=', $now)->get();
      Log::info($data_payment);
      if(count($data_payment) > 0){
         foreach($data_payment as $row){
            $payment = $this->makePayment($row['company_id'], $row['plan_id']);
         }
      }
   }

   public function saveDataPayment($request){
      Log::info($request);

      $credit_name = $request['credit_name'];
      $customer_phone = $request['customer_phone'];
      $address = $request['address'];
      $country = $request['country'];
      $data_card = $request['data'];
      $company_id = $request['company_id'];
      $status = 1;

      $decode = $this->decoder($data_card);

      $credit_card = $data_card;
      $secure_code = $decode['secure_code'];
      $type_card = $decode['card_brand'];
      $exp = $decode['credit_exp'];

      $expiration = explode("/",$exp);
      Log::info("EXPIRATION ");
      Log::info($expiration);
      $exp_month = $expiration[0];
      $exp_year = $expiration[1];

      $payment = new CustomerPayment;
      $payment->customer_phone = $customer_phone;
      $payment->credit_name = $credit_name;
      $payment->address = $address;
      $payment->country = $country;
      $payment->company_id = $company_id;
      $payment->credit_card = $credit_card;
      $payment->secure_code = Crypt::encrypt($secure_code);
      $payment->credit_expmonth = Crypt::encrypt($exp_month);
      $payment->credit_expyear = Crypt::encrypt($exp_year);
      $payment->card_brand = $type_card;
      $payment->save();

      if($payment->id){
         return "success";
      }else{
         return "error";
      }
   }

   public function boo(){
      $dt = \Carbon\Carbon::now();
      $now = $dt->toDateString();

      $data_payment = CompanyPlan::where('automatic_payment', '=', 1)->where('expiration_date', '=', $now)->get();

      if(count($data_payment) > 0){
         foreach($data_payment as $row){
            $payment = $this->makePayment($row['company_id'], $row['plan_id']);
         }
      }
   }

   public function makePayment($company_id, $plan_id){

      $dt = \Carbon\Carbon::now();
      $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
      $code_invoice = substr(str_shuffle($permitted_chars), 0, 10);

      $client = new \GuzzleHttp\Client();
      $response = $client->request('POST', 'https://api-payments.red4g.net/api/auth/login', [
         'form_params' => [
            'email' => 'cloud.shield@red5g.com',
            'password' => 'DjE!dRe1Hx1M8L',
         ]
      ]);

      $response = $response->getBody()->getContents();
      $result = json_decode($response, true);

      if(isset($result['access_token'])){

         $token = $result['access_token'];
         $data = CustomerPayment::join('company_plan', 'customer_payment.company_id', '=', 'company_plan.company_id')
            ->join('plans', 'company_plan.plan_id', '=', 'plans.id')
            ->where('customer_payment.company_id', '=', $company_id)
            ->select('customer_payment.*', 'plans.price', 'plans.id as plan_id', 'company_plan.expiration_date', 'plans.duration as duration')
            ->get();

         foreach($data as $value){
            //Log::info($value);
            $customer_id = $value['id'];
            $company_id = $value['company_id'];
            $total = $value['price'];
            $plan_id = $value['plan_id'];

            $duration = $value['duration'];

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://api-payments.red4g.net/api/cloudshield/card/debit', [
               'form_params' => [
                  'card' => $value['credit_card'],
                  'amount' => $total,
               ],
               'headers' => [
                   'Authorization' => 'Bearer '. $token,
                   'Accept'        => 'application/json',
               ]
            ]);

            $response = $response->getBody()->getContents();
            $result = json_decode($response, true);
            Log::info($result);
            $arr_invoice = [];

            if($result['code'] == 400){
               return $result['message'];
            }elseif($result['code'] == 200){
               $status_transaction = $result['data']['status'];

               if($status_transaction == "APPROVED"){

                  if($duration == "yearly"){
                     $date_exp = $dt->addYear();
                     $expiration = $date_exp->toDateString();
                  }else{
                     $date_exp = $dt->addMonth();
                     $expiration = $date_exp->toDateString();
                  }

                  $company_plan = DB::table('company_plan')
                     ->where('company_id', '=', $company_id)
                     ->update(['plan_id' => $plan_id, 'expiration_date' => $expiration]);

                  $arr_invoice = array(
                     0 => array(
                        'customer_id' => $customer_id,
                        'company_id' => $company_id,
                        'invoice_code' => $code_invoice,
                        'total' => $total,
                        'authorization_number' => $result['data']['referenceid'],
                        'payment_method_id' => 1,
                        'credit_charge' => 0,
                        'extra_charges' =>0,
                        'plan_id' => $plan_id,
                        'status_transaction' => $status_transaction,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                     )
                  );

                  $insert = DB::table('invoice')->insert($arr_invoice);
               }else{
                  $message = $result['data']['error'];
                  $arr_invoice = array(
                     0 => array(
                        'customer_id' => $customer_id,
                        'company_id' => $company_id,
                        'invoice_code' => $code_invoice,
                        'total' => $total,
                        'payment_method_id' => 1,
                        'credit_charge' => 0,
                        'extra_charges' =>0,
                        'plan_id' => $plan_id,
                        'status_transaction' => $status_transaction,
                        'message' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                     )
                  );

                  $insert = DB::table('invoice')->insert($arr_invoice);
                  $id = DB::getPdo()->lastInsertId();

                  $issue_payment = DB::table('customer_payment_issues')->insert(
                      [
                        'customer_payment_id' => $customer_id,
                        'company_id' => $company_id,
                        'invoice_id' => $id,
                        'status_payment' => $status_transaction,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                      ]
                  );
               }

               if($insert){
                  return $status_transaction;
               }else{
                  return "error";
               }
            }
         }
      }
   }

   public function getInvoices(Request $request){

      $userLog = JWTAuth::toUser($request['token']);
	  	$role_user = $userLog->roles->first()->name;

     	if($role_user == "superadmin" || $role_user == "ventas"){
         $company_id = $request['company_id'];

         $invoice = Invoice::join('company_plan', 'invoice.company_id', '=', 'company_plan.company_id')
            ->join('plans', 'invoice.plan_id', '=', 'plans.id')
            ->join('payment_methods', 'invoice.payment_method_id', '=', 'payment_methods.id')
            ->where('invoice.company_id', '=', $company_id)
            ->select('invoice.*', 'plans.price', 'plans.id as plan_id', 'plans.name', 'payment_methods.payment_name as payment_name')
            ->orderBy('invoice.created_at', 'desc')
            ->get();
     	}else{
         $company_id = $userLog['company_id'];
        	$invoice = Invoice::join('company_plan', 'invoice.company_id', '=', 'company_plan.company_id')
            ->join('plans', 'invoice.plan_id', '=', 'plans.id')
            ->join('payment_methods', 'invoice.payment_method_id', '=', 'payment_methods.id')
            ->where('invoice.company_id', '=', $company_id)
            ->select('invoice.*', 'plans.price', 'plans.id as plan_id', 'plans.name', 'payment_methods.payment_name as payment_name')
            ->orderBy('invoice.created_at', 'desc')
            ->get();
     	}

      if(count($invoice) > 0){
         return response()->json([
            'data' => $invoice
         ]);
      }else{
         return response()->json([
            'data' => []
         ]);
      }
   }

   public function manualPayment(Request $request){
      #1 -> updated
      #0 -> insert
      Log::info($request);

      $status = $request['status'];

      $user = JWTAuth::toUser($request['token']);
      $company_id = $user['company_id'];
      $credit_name = $request['credit_name'];
      $data_card = $request['data'];
      $plan_id = $request['plan_id'];
      $status = 1;

      $decode = $this->decoder($data_card);
      Log::info($decode);
      $credit_card = $data_card;
      $secure_code = $decode['secure_code'];
      $type_card = $decode['card_brand'];
      $exp = $decode['credit_exp'];

      $expiration = explode("/",$exp);
      $exp_month = $expiration[0];
      $exp_year = $expiration[1];

      if($status == 0){//significa que se va a insertar el registro

         $payment = new CustomerPayment;
         $payment->customer_phone = $customer_phone;
         $payment->credit_name = $credit_name;
         $payment->address = $address;
         $payment->country = $country;
         $payment->company_id = $company_id;
         $payment->credit_card = $credit_card;
         $payment->secure_code = Crypt::encrypt($secure_code);
         $payment->credit_expmonth = Crypt::encrypt($exp_month);
         $payment->credit_expyear = Crypt::encrypt($exp_year);
         $payment->card_brand = $type_card;
         $payment->save();

         if($payment->id){
            $new_payment = $this->makePayment($company_id, $plan_id);
            Log::info($new_payment);
         }else{
            $new_payment = "error";
         }

      }else{//significa que se va a actualizar

         $payment = DB::table('customer_payment')
            ->where('company_id', '=', $company_id)
            ->update(
               [
                  'credit_name' => $credit_name,
                  'company_id' => $company_id,
                  'credit_card' => $credit_card,
                  'secure_code' => Crypt::encrypt($secure_code),
                  'credit_expmonth' => Crypt::encrypt($exp_month),
                  'credit_expyear' => Crypt::encrypt($exp_year),
                  'card_brand' => $type_card
               ]
            );

         $new_payment = $this->makePayment($company_id, $plan_id);
      }

      if($new_payment == "APPROVED"){
         return response()->json([
             'success' => [
                'message' => 'Pago hecho correctamente',
                'status_code' => 200
             ]
         ]);
      }else{
         return response()->json([
	          'error' => [
	             'message' => 'Error al efectuar el pago.',
	             'status_code' => 20
	          ]
	       ]);
       }
   }

   public function paymentNow(Request $request){
      $user = JWTAuth::toUser($request['token']);
      $company_id = $user['company_id'];

      $data = CompanyPlan::where('company_id', '=', $company_id)->get();

      foreach($data as $row){
         $new_payment = $this->makePayment($company_id, $row['plan_id']);
      }

      if($new_payment == "APPROVED"){
      //if(isset($new_payment)){
         return response()->json([
             'success' => [
                'message' => 'Pago realizado correctamente',
                'status_code' => 200
             ]
         ]);
      }else{
         return response()->json([
	          'error' => [
	             'message' => 'Error al efectuar el pago.',
	             'status_code' => 20
	          ]
	       ]);
       }
   }

   public function typePayment(Request $request){

      $user = JWTAuth::toUser($request['token']);
      $company_id = $user['company_id'];

      $automatic = CompanyPlan::where('company_id', '=', $company_id)->pluck('automatic_payment');
      $automatic_id = str_replace(str_split('[]'), '', $automatic);

      return response()->json([
         'data' => $automatic_id
      ]);
   }

   public function changePaymentType(Request $request){
      Log::info($request);
      $company_id = $request['company_id'];
      $automatic = $request['automatic'];

      $payment = DB::table('company_plan')
         ->where('company_id', '=', $company_id)
         ->update(['automatic_payment' => $automatic]);

      if($payment){
         return response()->json([
             'success' => [
                'message' => 'Pago automático actualizado',
                'status_code' => 200
             ]
         ]);
      }else{
         return response()->json([
	          'error' => [
	             'message' => 'No se pudo actualizar el pago automático.',
	             'status_code' => 20
	          ]
	       ]);
       }
   }
}
