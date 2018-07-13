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
            Route::get('/access_rules', 'CheckpointController@getRules');
        });

        Route::group(['prefix' => 'access_control'], function(){
            Route::get('/companies_data', 'AccessController@getDataCompanies'); //GET DATA COMPANIES

            Route::post('/newCompany', 'AccessController@addCompany'); //ADD NEW COMPANY

            Route::post('/update_company', 'AccessController@updateCompany'); //UPDATE COMPANY

            Route::post('/delete_company', ['as' => 'access_control/delete_company', 'uses' => 'AccessController@destroy']); //DELETE COMPANY
        });

        Route::group(['prefix' => 'user'], function(){
            Route::get('/users_data', 'UserController@getDataUsers'); //GET ALL USERS

            Route::post('/edit_user', 'UserController@updateInformation'); //UPDATE USER

            Route::post('/delete', ['as' => 'user/delete', 'uses' => 'UserController@destroy']); //DELETE USER

            Route::get('/roles', 'UserController@getRolesData');
        });

        Route::group(['prefix' => 'layers'], function(){
            Route::get('/get_objects_server', 'LayersController@getObjectByServers');

            Route::post('/add_ip_list', 'LayersController@addIpList');

            Route::get('/get_ip_list', 'LayersController@getIpsList');

            Route::post('/remove_ip_list', 'LayersController@removeIpList');

            Route::get('/test', 'LayersController@test');

            Route::post('/edit_ip_list', 'LayersController@editIps');
        });

        Route::get('errors/sendEmailAlarm', 'CheckpointController@sendEmailAlarm');

        Route::get('settings/get_countries', 'SettingController@getCountriesData');
    });

    Route::post('auth/api_login', 'Auth\AuthController@api_login');
});
