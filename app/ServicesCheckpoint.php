<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicesCheckpoint extends Model
{
   protected $table = "fw_services_ch";
   protected $dateFormat = 'Y-m-d H:i:s';
}
