<?php

namespace Cms;

use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider 
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Cms::class, function () {
            return new Cms();
        });
        
        $this->app->alias(Cms::class, 'cms');
    }
}
