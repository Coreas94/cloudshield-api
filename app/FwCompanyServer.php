<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FwCompanyServer extends Model
{
    protected $table = 'fw_companies_servers';

    protected $fillable = ['id',
    						'company_id',
    						'server_id',
    						'description',
    						'created_by',
    						'updated_by',
    						'created_at',
    						'updated_at',
    						'deleted_at'];

    public function company(){
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function server(){
        return $this->belongsTo('App\FwServer', 'server_id');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

}
