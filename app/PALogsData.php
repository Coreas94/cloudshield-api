<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class PALogsData extends Eloquent{
   protected $connection = 'mongodb';
   protected $collection = 'pa_log_data';
   protected $dateFormat = 'Y-m-d H:i:s';
}
