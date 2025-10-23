<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
    
        $user = User::where('email', $validated['email'])->first();
    
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        // Xóa token hiện tại
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful']);
    }
}
