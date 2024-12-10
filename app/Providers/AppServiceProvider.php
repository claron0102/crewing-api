<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
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
   public function boot()
    {
        $this->mapApiRoutes(); // Call the function for API route mapping
    }

    /**
     * Define the "api" routes for the application.
     *
     * @return void
     */
    public function mapApiRoutes()
    {
        Route::prefix('v1')  // Prefix for API versioning (e.g., /v2/)
            ->middleware('api')  // Apply the 'api' middleware group
            ->group(base_path('routes/api.php'));  // Register routes from api.php
    }
}
