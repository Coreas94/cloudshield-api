<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailPlan extends Model
{
   use SoftDeletes;

   protected $table = "detail_plan";
   protected $dates = ['deleted_at'];
}
