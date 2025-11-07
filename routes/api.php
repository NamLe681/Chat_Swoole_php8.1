<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\Roomcontroller;
use App\Http\Controllers\API\VoiceMessageController;


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


Route::post('messages/voice/{room}',[VoiceMessageController::class,'storeVoice'])->middleware(middleware: 'auth:sanctum');
Route::apiResource('rooms', 'App\Http\Controllers\API\RoomController');
Route::post('rooms/{room}/messages', [RoomController::class, 'postmessage']);
Route::get('rooms/{room}/messages', 'App\Http\Controllers\API\RoomController@messages');

Route::get('rooms',[RoomController::class,'show']);
Route::post('rooms/{room}/adduser/{userId}', [RoomController::class, 'addUserToRoom'])->middleware(middleware: 'auth:sanctum');
Route::post('/rooms', 'App\Http\Controllers\API\RoomController@store')->middleware(middleware: 'auth:sanctum');
Route::post('/register', 'App\Http\Controllers\API\RegisterController@register');
Route::middleware(['web'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
});

