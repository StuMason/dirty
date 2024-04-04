<?php

namespace App\Providers;

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
        if (file_exists('/mnt/store')) {
            app()->useEnvironmentPath('/mnt/store');
        }

        app()->useStoragePath(env('APP_STORAGE', app()->storagePath()));

        if (env('APP_CF_URL')) {
            url()->forceRootUrl(env('APP_CF_URL'));
        }

        if (! is_dir(config('view.compiled'))) { 
            mkdir(config('view.compiled'), 0755, true); 
        }

        if (! is_dir("/tmp/storage/framework/cache")) {
            mkdir("/tmp/storage/framework/cache", 0777, true);
        }
    }
}
