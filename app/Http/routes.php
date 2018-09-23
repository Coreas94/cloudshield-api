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

Route::get('prueba', 'Controller@test');

Route::group(['middleware' => ['api'], 'prefix' => 'api/v2'], function(){

   Route::group(['middleware' => ['jwt-auth']], function(){

      Route::group(['prefix' => 'checkpoint'], function(){
         Route::post('/test', 'CheckpointController@test');

         Route::post('/new_object', 'CheckpointController@createDynamicObject');
         Route::post('/assignIpObject', 'CheckpointController@assignIpObject');
         Route::post('/removeIpObject', 'CheckpointController@removeIpObject');
         Route::post('/removeObject', 'CheckpointController@removeObject');
         Route::post('/add_rule', 'CheckpointController@addRules');
         Route::post('/object_rule', 'CheckpointController@addObjectsToRule');
         Route::post('/getIps', 'CheckpointController@getIpsByObject');
         Route::post('/add_new_rule', 'CheckpointController@addNewRule');
         Route::post('/disable_rule', 'CheckpointController@disableRule');
         Route::post('/remove_rule', 'CheckpointController@removeRule');
         Route::post('/move_rule', 'CheckpointController@moveRule');
         Route::post('/get_rules_company', 'CheckpointController@getRulesByCompany');
         Route::post('/edit_ips_object', 'CheckpointController@editIpsObject');
         Route::post('/getAllIps', 'CheckpointController@getAllIpsByObject');
     		//Ruta para traer los rangos de ip para eliminar una
     		Route::post('/getIpsForDelete', 'CheckpointController@getAllIpsForDelete');

         Route::get('/order_objects', 'CheckpointController@orderObjectsBD'); //PEND
         Route::get('/get_objects', 'CheckpointController@getDynamicObjects');
         Route::get('/access_rules', 'CheckpointController@getRules');
         //Route::get('/get_objects', 'CheckpointController@getDynamicObjects');
         Route::get('/get_objects_rules', 'CheckpointController@getObjectsRules');
         Route::get('/install', 'CheckpointController@installPolicy');
         Route::get('/discard', 'CheckpointController@discardChanges');
         Route::get('/saveServices', 'CheckpointController@saveServicesCheckpoint');
         Route::get('/getServices', 'CheckpointController@getServicesCheckpoint');
         Route::get('/addNewList', 'CheckpointController@newListRules');
     		//Ruta para obtener cambios desde el checkpoint
     		Route::get('/get_changes', 'CheckpointController@getChanges');
      });

      Route::group(['prefix' => 'fortisiem'], function(){
         Route::get('/organizations', 'FortisiemController@getOrganizations');
         Route::get('/incidents', 'FortisiemController@getIncidents');
         Route::post('/new_organization', 'FortisiemController@saveNewOrganization');
         Route::get('/run_script_logs', 'FortisiemController@runScriptLogs');

         Route::get('/get_logs', 'FortisiemController@getDataLogs');
         Route::get('/read_file', 'FortisiemController@readJsonFile');
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
         Route::post('/edit_ip_list', 'LayersController@editIps');
      });

      Route::get('errors/sendEmailAlarm', 'CheckpointController@sendEmailAlarm');
      Route::get('settings/get_countries', 'SettingController@getCountriesData');

      Route::get('validate_token', 'UserController@verifyToken');
   });

   Route::post('auth/api_login', 'Auth\AuthController@api_login');

   Route::post('auth/signup', ['as' =>'auth/signup', 'uses' => 'Auth\AuthController@api_signup']);
});
