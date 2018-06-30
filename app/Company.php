<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model{

   use SoftDeletes;

   protected $table = "fw_companies";
   protected $dates = ['deleted_at'];

}
