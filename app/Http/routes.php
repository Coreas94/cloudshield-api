<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::group(['middleware' => ['api'], 'prefix' => 'api/v2'], function(){
    Route::group(['middleware' => ['jwt-auth']], function(){
        Route::post('/test', 'CheckpointController@test');
    });/*
    Route::post('auth/api_login', 'Auth\AuthController@api_login');*/
    //Replace
    Route::group(['prefix' => 'checkpoint'], function(){

    });
});
