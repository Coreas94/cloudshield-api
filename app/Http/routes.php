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
        Route::group(['prefix' => 'checkpoint'], function(){
            Route::post('/test', 'CheckpointController@test');
            Route::post('/new_object', 'CheckpointController@createDynamicObject');

            Route::get('/order_objects', 'CheckpointController@orderObjectsBD'); //PEND
            Route::get('/get_objects', 'CheckpointController@getDynamicObjects');
        });
    });
    Route::post('auth/api_login', 'Auth\AuthController@api_login');
});
