<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class User extends Authenticatable{
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	*/

	use SoftDeletes, EntrustUserTrait {
		SoftDeletes::restore insteadof EntrustUserTrait;
		EntrustUserTrait::restore insteadof SoftDeletes;
  	}

  	protected $with = ['roles'];

	protected $dates = ['deleted_at'];

	protected $fillable = [
		'name', 'email', 'password',
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];
}
