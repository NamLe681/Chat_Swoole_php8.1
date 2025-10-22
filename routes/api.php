<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// routes/api.php
Route::apiResource('rooms', 'App\Http\Controllers\API\RoomController');
Route::get('rooms/{room}/messages', 'App\Http\Controllers\API\RoomController@messages');
Route::post('/register', 'App\Http\Controllers\API\RegisterController@register');
Route::post('/login', 'App\Http\Controllers\API\LoginController@login');
Route::post('/logout', 'App\Http\Controllers\API\LoginController@logout');
