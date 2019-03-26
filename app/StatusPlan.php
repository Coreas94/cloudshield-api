<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusPlan extends Model
{
   use SoftDeletes;

   protected $table = "status_plan";
   protected $dates = ['deleted_at'];
}
