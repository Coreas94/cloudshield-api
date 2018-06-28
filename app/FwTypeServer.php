<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FwTypeServer extends Model
{
    protected $table = 'fw_types_servers';

    protected $fillable = ['id',
    						'name',
    						'description',
    						'created_by',
    						'updated_by',
    						'created_at',
    						'updated_at',
    						'deleted_at'];

    public function fw_server(){
        return $this->hasMany('App\FwServer');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

}
