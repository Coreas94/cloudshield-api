<?php

namespace App\Listeners;

use Codemash\Socket\Events\MessageReceived;
use Codemash\Socket\Events\ClientConnected;
use Codemash\Socket\Events\ClientDisconnected;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use JWTAuth;
use File;

use Illuminate\Support\Facades\DB;

class MessageEventListener {

   /*public function __construct(Request $request){
      $this->request = $request;
   }*/

   public function onMessageReceived(MessageReceived $event)
   {
      //\Log::info("llega al socket2");
      $message = $event->message;
      //\Log::info(print_r($message->data, true));

      //If the incomming command is 'sendMessageToOthers', forward the message to the others.
      if ($message->command === 'sendMessageToOthers') {
         //To get the client sending this message, use the $event->from property.
         //To get a list of all connected clients, use the $event->clients pointer.
         $others = $event->onlyMe();
         //\Log::info($others);

         foreach ($others as $client) {
            //The $message->data property holds the actual message
            $client->send('newMessage', $message->data);
         }
      }elseif ($message->command === 'installIps') {

         $token = $message->data;

         $json2 = json_decode(json_encode($token), true);

         //Log::info(print_r($json2, true));
         //$userLog = JWTAuth::toUser($token->scalar);
         //$userLog = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEsImlzcyI6Imh0dHA6Ly8xNzIuMTYuMjAuODUvY29udHJvbDRfYXBpMi9hcGkvdjIvYXV0aC9hcGlfbG9naW4iLCJpYXQiOjE1NDAzMjU3NDEsImV4cCI6MTU0MDM1ODE0MSwibmJmIjoxNTQwMzI1NzQxLCJqdGkiOiJUM0NLNHNCZ2NWZmRaelFOIn0.Cb_lb3zwdEbiVCqZQwmRGmVmpS5oe57q3PkV6-ndZOA";
         //\Log::info($userLog);
         $api_token = $json2['api_token'];
         $company_id = $json2['company_id'];
         $company_data = DB::table('fw_companies')->where('id', $company_id)->get();
         $company_data2 = json_decode(json_encode($company_data), true);

         $name_company = $company_data2[0]['name'];
         $token_company = $company_data2[0]['token_company'];

         $path = storage_path() ."/app/".$name_company."/".$api_token.".json";

         if(File::exists($path)){
            $json_response = json_decode(file_get_contents($path), true);
         }else{
            $arreglo = array("success" => "", "error" => "", "info" => 0);

            $json_response = json_encode($arreglo);
            \Storage::put($name_company.'/'.$token_company.'.json', $json_response);
         }

         $others = $event->onlyMe();
         #\Log::info($others);
         foreach ($others as $client){
            $client->send('installIps', $json_response);
         }
      }
   }

   public function onConnected(ClientConnected $event)
   {
      //Not used in this example.
      //\Log::info("Llega al socket");

      $others = $event->allOtherClients();
   }

   public function onDisconnected(ClientDisconnected $event)
   {
      // Not used in this example.
   }

   /**
   * Register the listeners for the subscriber.
   *
   * @param  Illuminate\Events\Dispatcher  $events
   */
   public function subscribe($events)
   {
      $events->listen(
         'Codemash\Socket\Events\ClientConnected',
         'App\Listeners\MessageEventListener@onConnected'
      );

      $events->listen(
         'Codemash\Socket\Events\MessageReceived',
         'App\Listeners\MessageEventListener@onMessageReceived'
      );

      $events->listen(
         'Codemash\Socket\Events\ClientDisconnected',
         'App\Listeners\MessageEventListener@onDisconnected'
      );
   }
}
