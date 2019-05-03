<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RulesExceptionObjects extends Model{

   use SoftDeletes;

   protected $table = "fw_rules_exception_objects";
   protected $dates = ['deleted_at'];
}
