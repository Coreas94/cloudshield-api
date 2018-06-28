<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FwSectionAccess extends Model
{
   use SoftDeletes;

   protected $table = "fw_access_sections_ch";
   protected $dates = ['deleted_at'];
}
