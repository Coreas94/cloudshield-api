<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyPlan extends Model
{
   use SoftDeletes;

   protected $table = "company_plan";
   protected $dates = ['deleted_at'];
}
