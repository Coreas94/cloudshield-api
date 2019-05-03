<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FwRuleException extends Model{

   use SoftDeletes;

   protected $table = "fw_rules_exception";
   protected $dates = ['deleted_at'];
}
