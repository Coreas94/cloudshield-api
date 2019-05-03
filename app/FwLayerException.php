<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FwLayerException extends Model{

   use SoftDeletes;

   protected $table = "fw_layer_exception";
   protected $dates = ['deleted_at'];

}
