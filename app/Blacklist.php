<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model{

   protected $connection = 'c4_sql';
   protected $table = 'blacklist_logs';
   protected $dateFormat = 'Y-m-d H:i:s';
}