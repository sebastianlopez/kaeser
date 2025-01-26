<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::post('/', 'App\Http\Controllers\integrateController@movetokeaser');

Route::post('/update-status', 'App\Http\Controllers\integrateController@updateStatus');

Route::post('/update-kaeser', 'App\Http\Controllers\integrateController@updateKaeser');

Route::post('/delete-kaeser', 'App\Http\Controllers\integrateController@deleteKaeser');

Route::post('/upddate-company-kaeser', 'App\Http\Controllers\integrateController@updateCompany');


