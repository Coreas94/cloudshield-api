<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ThreatIps extends Eloquent
{
   protected $connection = 'mongodb';
   protected $collection = 'threat_ips';
   protected $dateFormat = 'Y-m-d H:i:s';
}
