<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        View::composer('*', function ($view) {
            $view->with('user', Auth::user());
        });
        
        $stage = env('APP_STAGE', 'STAGING');

        $ecfniUrl = $stage === 'LIVE'
            ? 'https://app.ecnfi.com'
            : 'https://demo.ecnfi.com';
    
        // Set it in config
        Config::set('services.ecnfi.url', $ecfniUrl);
    }
}
