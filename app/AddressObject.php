<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddressObject extends Model
{
   use SoftDeletes;

    protected $table = "fw_address_objects";
    protected $dates = ['deleted_at'];
}
