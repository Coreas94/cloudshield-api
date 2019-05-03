<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ThreatIps extends Eloquent{

   use SoftDeletes;

   protected $connection = 'mongodb';
   protected $collection = 'threat_ips';
   protected $dateFormat = 'Y-m-d H:i:s';
   protected $dates = ['deleted_at'];
}
