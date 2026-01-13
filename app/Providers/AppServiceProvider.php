<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        // Register custom middleware
        $this->app['router']->aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);
        
        // Force HTTPS in production (required for Cloudflare/reverse proxy)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Use custom Tailwind pagination view
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');
    }
}
