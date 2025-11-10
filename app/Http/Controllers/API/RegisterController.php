<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;


class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);
        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = bcrypt($validated['password']);
        $user->save(); // 

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify.custom',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id]
        );


        Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationUrl));

        return response()->json([
            'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để xác nhận tài khoản.',
            'user' => $user,
        ], 201);
        }
    
}