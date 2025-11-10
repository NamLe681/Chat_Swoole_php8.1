<?php

use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\Roomcontroller;
use App\Http\Controllers\API\VoiceMessageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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
// email verify

//emai

Route::get('/verify-email/{id}', function (Request $request, $id) {
    if (! $request->hasValidSignature()) {
        return response()->json(['message' => 'Liên kết xác thực không hợp lệ hoặc đã hết hạn.'], 403);
    }

    $user = \App\Models\User::findOrFail($id);

    if ($user->email_verified_at) {
        return response()->json(['message' => 'Email đã được xác thực trước đó.']);
    }

    $user->email_verified_at = now();
    $user->save();

    return response()->json(['message' => 'Xác thực email thành công!']);
})->name('verification.verify.custom');


Route::post('messages/voice/{room}', [VoiceMessageController::class, 'storeVoice'])->middleware(middleware: 'auth:sanctum');
Route::apiResource('rooms', 'App\Http\Controllers\API\RoomController');
Route::post('rooms/{room}/messages', [RoomController::class, 'postmessage']);
Route::get('rooms/{room}/messages', [RoomController::class, 'messages']);

Route::get('rooms', [RoomController::class, 'show']);
Route::post('rooms/{room}/add-user/{user}', [RoomController::class, 'addUserToRoom']);
Route::post('/rooms', [RoomController::class, 'store'])->middleware(middleware: 'auth:sanctum');
Route::post('/register', [RegisterController::class, 'store']);
Route::middleware(['web'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/get/users',[UserController::class,'index']);
});
