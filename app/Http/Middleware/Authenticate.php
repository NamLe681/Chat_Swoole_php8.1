<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    // App/Http/Middleware/Authenticate.php
    protected function redirectTo(Request $request): ?string
    {
        // Nếu là request API, trả về null (sẽ trả về lỗi 401 Unauthorized)
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }
        
        return route('login');
    }
}
