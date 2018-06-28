<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LogsData extends Eloquent{
   protected $connection = 'mongodb';
   protected $collection = 'logs_data';
   protected $dateFormat = 'Y-m-d H:i:s';
}
