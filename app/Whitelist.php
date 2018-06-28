<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Whitelist extends Model{

   protected $connection = 'c4_sql';
   protected $table = 'whitelist_logs';
   protected $dateFormat = 'Y-m-d H:i:s';
   //  public $timestamps = false;
}
