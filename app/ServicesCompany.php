<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicesCompany extends Model
{
   use SoftDeletes;

   protected $table = "services_company";
   protected $dates = ['deleted_at'];
}
