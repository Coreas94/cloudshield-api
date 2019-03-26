<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
   use SoftDeletes;

   protected $table = "invoice";
   protected $dates = ['deleted_at'];
}
