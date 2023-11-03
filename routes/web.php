<?php

use Illuminate\Support\Facades\Route;

/*
	TITLE 변경시 Vue.JS 도 변경 하시길 바랍니다
*/

Route::group(['middleware' => 'visit.check'], function(){

    Route::any('/', 'App\Http\Controllers\VueController@main');

    Route::any('/exchange', 'App\Http\Controllers\VueController@exchange');

    Route::any('/liquidity', 'App\Http\Controllers\VueController@liquidity');

    Route::any('/addLiquidity', 'App\Http\Controllers\VueController@addLiquidity');

    Route::any('/farm', 'App\Http\Controllers\VueController@farm');

    Route::any('/pools', 'App\Http\Controllers\VueController@pools');

    Route::any('/info', 'App\Http\Controllers\VueController@info');

    Route::any('/info/token/{token}', 'App\Http\Controllers\VueController@infoToken');

    Route::any('/info/pool/{lp}', 'App\Http\Controllers\VueController@infoPool');

    Route::any('/removeLiquidity', 'App\Http\Controllers\VueController@removeLiquidity');

    Route::any('/contacts', 'App\Http\Controllers\VueController@contacts');

    Route::any('/help', 'App\Http\Controllers\VueController@help');

    Route::any('/service', 'App\Http\Controllers\VueController@service');

    Route::any('/privacy', 'App\Http\Controllers\VueController@privacy');


});

