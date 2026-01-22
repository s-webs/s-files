<?php

namespace SSystems\SFiles;

use Illuminate\Support\ServiceProvider;
use SSystems\SFiles\Middleware\FileManagerAuth;
use SSystems\SFiles\Middleware\FileManagerRateLimit;

class SFilesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Регистрация конфигурации
        $this->mergeConfigFrom(
            __DIR__.'/../config/sfiles.php',
            'sfiles'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикация конфигурации
        $this->publishes([
            __DIR__.'/../config/sfiles.php' => config_path('sfiles.php'),
        ], 'sfiles-config');

        // Публикация views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sfiles'),
        ], 'sfiles-views');

        // Публикация assets
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/sfiles'),
            __DIR__.'/../resources/css' => resource_path('css/vendor/sfiles'),
        ], 'sfiles-assets');

        // Публикация lang (для кастомизации переводов)
        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/sfiles'),
        ], 'sfiles-lang');

        // Загрузка views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sfiles');

        // Регистрация routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Регистрация middleware
        $this->app['router']->aliasMiddleware(
            'sfiles.auth',
            FileManagerAuth::class
        );

        $this->app['router']->aliasMiddleware(
            'sfiles.ratelimit',
            FileManagerRateLimit::class
        );
    }
}
