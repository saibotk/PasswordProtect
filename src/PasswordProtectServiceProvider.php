<?php

namespace Michaelmetz\Passwordprotect;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for the PasswordProtect class
 *
 * @author     Michael Metz
 * @link       https://github.com/Michael-Metz
 */
class PasswordProtectServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {
        //either do this or manuel register it in kernal.php
        //$router->aliasMiddleware('passwordprotect', 'Michaelmetz\Passwordprotect\Middleware\PasswordProtect');

        $this->loadViewsFrom(__DIR__.'/views', 'passwordprotect');

        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/passwordprotect'),
        ]);
        $this->publishes([
            __DIR__.'/config/passwordprotect.php' => config_path('passwordprotect.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app->make('Michaelmetz\Passwordprotect\PasswordProtectController');
    }
}
