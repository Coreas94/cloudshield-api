<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LayerSecurity extends Model{

   use SoftDeletes;

   protected $table = "layers_security_list";
   protected $dates = ['deleted_at'];
}
