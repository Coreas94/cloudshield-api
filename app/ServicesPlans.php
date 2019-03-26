<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicesPlans extends Model
{
   use SoftDeletes;

   protected $table = "services_plans";
   protected $dates = ['deleted_at'];
}
