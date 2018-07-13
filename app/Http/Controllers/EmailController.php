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

class EmailController extends Controller
{
    public function sendEmailRules($name, $type){
     $title = 'Control Four - Alert';

     if($type == "error"){
       $data = 'Se informa que la regla '.$name.' no pudo ser eliminada del checkpoint.';
     }else{
       $data = 'Se informa que no se puede conectar con el checkpoint para eliminar la regla '.$name;
     }

       Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('Control4 - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'Control Four');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('hcastillo@red4g.net');
       });

       return response()->json(['message' => 'Request completed']);
     }

    public function sendEmailSection($name, $type){
     $title = 'Control Four - Alert';

     if($type == "error"){
       $data = 'Se informa que la regla '.$name.' no pudo ser eliminada del checkpoint.';
     }else{
       $data = 'Se informa que no se puede conectar con el checkpoint para eliminar la regla '.$name;
     }

       Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('Control4 - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'Control Four');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('hcastillo@red4g.net');
       });

       return response()->json(['message' => 'Request completed']);
     }

    public function sendEmailObject($name, $type){

       $title = 'Control Four - Alert';

     if($type == "error"){
       $data = 'Se informa que el objeto '.$name.' no pudo ser eliminado del checkpoint.';
     }elseif($type == "error_ips") {
        $data = 'Se informa que no se pudieron eliminar las IPs asignadas al objeto '.$name;
     }else{
       $data = 'Se informa que no se puede conectar con el checkpoint para eliminar el objeto '.$name;
     }

       Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('Control4 - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'Control Four');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('hcastillo@red4g.net');
       });

       return response()->json(['message' => 'Request completed']);
    }

    public function sendEmailCompany($name, $type){

       $title = 'Control Four - Alert';

     if($type == "error"){
       $data = 'Se informa que la compañía '.$name.' no pudo ser eliminada.';
     }

       Mail::send('email.alert_error', ['title' => $title, 'data' => $data], function ($message){
         $message->subject('Control4 - Alarma de errores');
         $message->from('jcoreas@red4g.net', 'Control Four');
         #$message->to('servers-comment@request.red4g.net');
         $message->to('hcastillo@red4g.net');
       });

       return response()->json(['message' => 'Request completed']);

    }
}
