<?php

namespace Itsmedudes\LaravelWhatsapp;

use Itsmedudes\LaravelWhatsapp\Contracts\TokenResolverInterface;
use Itsmedudes\LaravelWhatsapp\TokenResolvers\DatabaseTokenResolver;
use Illuminate\Support\ServiceProvider;

class MetaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/meta.php', 'meta');

        $resolverClass = config('meta.token_resolver', DatabaseTokenResolver::class);
        $this->app->singleton(TokenResolverInterface::class, $resolverClass);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/meta.php' => config_path('meta.php'),
            ], 'meta-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/2024_01_01_000001_create_meta_credentials_table.php' =>
                    database_path('migrations/2024_01_01_000001_create_meta_credentials_table.php'),
            ], 'meta-migrations');
        }
    }
}
