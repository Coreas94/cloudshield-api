<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FwObject extends Model{

   use SoftDeletes;

   protected $table = 'fw_objects';
   protected $dates = ['deleted_at'];

}
