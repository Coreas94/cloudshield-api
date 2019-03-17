<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class HistoricalData extends Eloquent
{
   protected $connection = 'mongodb';
   protected $collection = 'historical_data';
   protected $dateFormat = 'Y-m-d H:i:s';
}
