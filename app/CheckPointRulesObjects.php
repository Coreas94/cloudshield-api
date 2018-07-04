<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckPointRulesObjects extends Model
{
   use SoftDeletes;

   protected $table = 'fw_rules_objects_ch';
   protected $dateFormat = 'Y-m-d H:i:s';

   protected $dates = ['deleted_at'];
}
