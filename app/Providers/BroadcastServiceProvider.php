<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Route;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    // app/Providers/BroadcastServiceProvider.php
    public function boot(): void
    {
        Broadcast::routes([
            Broadcast::routes(['middleware' => ['web', 'auth']])
        ]);

        require base_path('routes/channels.php');
    }
}
