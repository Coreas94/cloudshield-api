<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FwAccessRule extends Model
{
   use SoftDeletes;

   protected $table = "fw_access_rules_ch";
   protected $dates = ['deleted_at'];
}
