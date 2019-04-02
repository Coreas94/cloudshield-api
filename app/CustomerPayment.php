<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
   use SoftDeletes;

   protected $table = "customer_payment";
   protected $dates = ['deleted_at'];
}
