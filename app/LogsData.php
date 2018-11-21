<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LogsData extends Eloquent{
   protected $connection = 'mongodb';
   protected $collection = 'log_data';
   protected $dateFormat = 'Y-m-d H:i:s';

}
