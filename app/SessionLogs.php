<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class SessionLogs extends Eloquent{

   protected $connection = 'mongodb';
   protected $collection = 'session_logs';
   protected $dateFormat = 'Y-m-d H:i:s';
}
