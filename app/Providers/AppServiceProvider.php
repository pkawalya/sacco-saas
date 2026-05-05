<?php

namespace App\Providers;

use App\Listeners\FailedLoginListener;
use App\Listeners\LoginSecurityListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Security event listeners
        Event::listen(Login::class, LoginSecurityListener::class);
        Event::listen(Failed::class, FailedLoginListener::class);
    }
}
