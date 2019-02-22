<?php

   namespace App\Http\Controllers;

   use Illuminate\Http\Request;
   use phpseclib\Net\SFTP;
   use App\Jobs\senderEmailIp;
   use Mail;
   use Illuminate\Foundation\Bus\DispatchesJobs;

   use Illuminate\Support\Facades\DB;
   use Illuminate\Support\Collection;
   use Illuminate\Support\Facades\Log;
   use Illuminate\Support\Facades\Input;
   use Illuminate\Support\Facades\Session;

   use App\Http\Requests;

   class EmailController extends Controller{

   public function sendEmailRules($name, $type){
      $title = 'CloudShield - Alert';

      if($type == "error"){
         $data = 'Se informa que la regla '.$name.' no pudo ser eliminada del checkpoint.';
      }else{
         $data = 'Se informa que no se puede conectar con el checkpoint para eliminar la regla '.$name;
      }

      Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('CloudShield - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'CloudShield');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json(['message' => 'Request completed']);
   }

   public function sendEmailSection($name, $type){
      $title = 'CloudShield - Alert';

      if($type == "error"){
         $data = 'Se informa que la regla '.$name.' no pudo ser eliminada del checkpoint.';
      }else{
         $data = 'Se informa que no se puede conectar con el checkpoint para eliminar la regla '.$name;
      }

      Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('CloudShield - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'CloudShield');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json(['message' => 'Request completed']);
   }

   public function sendEmailObject($name, $type){

      $title = 'CloudShield - Alert';

      if($type == "error"){
         $data = 'Se informa que el objeto '.$name.' no pudo ser eliminado del checkpoint.';
      }elseif($type == "error_ips") {
         $data = 'Se informa que no se pudieron eliminar las IPs asignadas al objeto '.$name;
      }else{
         $data = 'Se informa que no se puede conectar con el checkpoint para eliminar el objeto '.$name;
      }

      Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('CloudShield - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'CloudShield');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json(['message' => 'Request completed']);
   }

   public function sendEmailCompany($name, $type){

      $title = 'CloudShield - Alert';

      if($type == "error"){
         $data = 'Se informa que la compañía '.$name.' no pudo ser eliminada.';
      }

      Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('CloudShield - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'CloudShield');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json(['message' => 'Request completed']);
   }

   public function sendEmailSSHObj($data){

      if($data['type_ssh'] == "add_object"){
         $title = 'CloudShield - Alert New Object';
         $msg = "Se informa que la empresa: ".$data['name_company']." agregó el siguiente objeto dinámico: ".$data['name_object'];

      }elseif($data['type_ssh'] == "remove_object"){
         $title = 'CloudShield - Alert Remove Object';
         $msg = "Se informa que la empresa: ".$data['name_company']." eliminó el siguiente objeto dinámico: ".$data['name_object'];

      }elseif($data['type_ssh'] == "add_ip_object") {
         $title = 'CloudShield - Alert New Range IP Object';
         //$msg
      }elseif($data['type_ssh'] == "remove_ip_object") {
         $title = 'CloudShield - Alert Remove Range IP Object';
      }

      Mail::send('email.alertssh', ['title' => $title, 'data' => $msg], function ($message){
         $message->subject('CloudShield - Alarma Informativa');
         $message->from('jcoreas@red4g.net', 'CloudShield');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json(['message' => 'Request completed']);
   }

   public function sendEmailSSHRange($data){

      if($data['type_ssh'] == "add_ip_object") {
         $title = 'CloudShield - Alert New Range IP Object';
         $msg = "Se informa que la empresa: ".$data['name_company']." modificó el siguiente objeto dinámico: ".$data['name_object'];
         $data2 = "Ya que se agregaron los siguientes rangos de IPs: ".implode(", ", $data['ips']);

      }elseif($data['type_ssh'] == "remove_ip_object") {
         $title = 'CloudShield - Alert Remove Range IP Object';
         $msg = "Se informa que la empresa: ".$data['name_company']." modificó el siguiente objeto dinámico: ".$data['name_object'];
         $data2 = "Ya que se eliminaron los siguientes rangos de IPs: ".implode(", ", $data['ips']);
      }

      Mail::send('email.alertssh', ['title' => $title, 'data' => $msg, "data2" => $data2], function ($message){
         $message->subject('CloudShield - Alarma Informativa');
         $message->from('jcoreas@red4g.net', 'CloudShield');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('jcoreas@red4g.net');
      });

      return response()->json(['message' => 'Request completed']);
   }

}
