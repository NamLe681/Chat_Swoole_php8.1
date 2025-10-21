<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    // public function login(Request $request){
    //     $validated = request -> validate([
    //         'email' => 'required|email',
    //         'password' => 'required|string|min:6',
    //     ]);
    //     $user = User::where('email','password')->fist();
    //     return response()->json(['message' => 'Login successful'], 200);
    // },

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
    
        $request->session()->invalidate();
    
        $request->session()->regenerateToken();
    
        return redirect('/');
    }
}
