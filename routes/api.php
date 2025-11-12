<?php

use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\Roomcontroller;
use App\Http\Controllers\API\VoiceMessageController;
use App\Http\Controllers\Api\SpotifyController;
use App\Http\Controllers\Api\UserController;
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

Route::post('messages/voice/{room}', [VoiceMessageController::class, 'storeVoice'])->middleware(middleware: 'auth:sanctum');
Route::apiResource('rooms', 'App\Http\Controllers\API\RoomController');
Route::post('rooms/{room}/messages', [RoomController::class, 'postmessage']);
Route::get('rooms/{room}/messages', [RoomController::class, 'messages']);

//Spotify Search route
Route::get('/spotify/search', [SpotifyController::class, 'search']);
Route::get('/spotify/track/{id}', [SpotifyController::class, 'getTrack']);
Route::post('spotify/music/{room}/', [SpotifyController::class, 'sendMusicMessage']);

Route::get('rooms', [RoomController::class, 'show']);
Route::post('rooms/{room}/add-user/{user}', [RoomController::class, 'addUserToRoom']);
Route::post('/rooms', [RoomController::class, 'store'])->middleware(middleware: 'auth:sanctum');
Route::post('/register', [RegisterController::class, 'store']);
Route::middleware(['web'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/get/users',[UserController::class,'index']);
});
