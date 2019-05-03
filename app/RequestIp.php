<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestIp extends Model{

   use SoftDeletes;

   protected $table = "request_ips";
   protected $dates = ['deleted_at'];
}
