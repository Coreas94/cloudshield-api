<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FwServer extends Model
{
    protected $table = 'fw_servers';

    protected $fillable = ['id',
    						'name',
    						'type_id',
    						'url',
    						'user',
    						'password',
    						'key',
    						'created_by',
    						'updated_by',
    						'created_at',
    						'updated_at',
    						'deleted_at'];


    public function type_server(){
        return $this->belongsTo('App\FwTypeServer', 'type_id');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

}
