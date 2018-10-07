<?php

namespace App\Listeners;

use Codemash\Socket\Events\MessageReceived;
use Codemash\Socket\Events\ClientConnected;
use Codemash\Socket\Events\ClientDisconnected;

class MessageEventListener {

   public function onMessageReceived(MessageReceived $event)
   {
      \Log::info("llega al socket2");
     $message = $event->message;
     //\Log::info(print_r($message->data, true));

      // If the incomming command is 'sendMessageToOthers', forward the message to the others.
      if ($message->command === 'sendMessageToOthers') {
         // To get the client sending this message, use the $event->from property.
         // To get a list of all connected clients, use the $event->clients pointer.
         $others = $event->onlyMe();
         \Log::info($others);

         foreach ($others as $client) {
            // The $message->data property holds the actual message
            $client->send('newMessage', $message->data);
         }

      }elseif ($message->command === 'installIps') {

         $data_success = Session::get('temp_data_succ');
         $data_error = Session::get('temp_data_err');

         $array_response = array('data_success' => $data_success, 'data_error' => $data_error);

         $others = $event->onlyMe();
         foreach ($others as $client) {
            $client->send('newMessage', $array_response);
         }
      }
   }

   public function onConnected(ClientConnected $event)
   {
     // Not used in this example.
      \Log::info("Llega al socket");

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
