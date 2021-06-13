<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('register', 'JWTAuthController@register');
    Route::post('login',    'JWTAuthController@login');
    Route::post('refresh',  'JWTAuthController@refresh');
    Route::get('logout',    'JWTAuthController@logout');
    Route::get('me',        'JWTAuthController@me');
    Route::post('update',   'JWTAuthController@update');
    Route::post('reminder', 'JWTAuthController@reminder');
});
