<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class CustomerIps extends Eloquent{

   protected $connection = 'mongodb';
   protected $collection = 'ips_customer';
   protected $dateFormat = 'Y-m-d H:i:s';
}
