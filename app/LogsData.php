<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LogsData extends Eloquent{
   protected $connection = 'mongodb';
   protected $collection = 'log_data';
   protected $dateFormat = 'Y-m-d H:i:s';


   // $collection->index('src_ip');
   // $collection->index('dst_ip');
   // $collection->index('receive_time');

   public function up(){
      Schema::table('log_data', function(Blueprint $table)
      {
         $table->index('src_ip');
         $table->index('dst_ip');
         $table->index('receive_time');
      });
   }

}
