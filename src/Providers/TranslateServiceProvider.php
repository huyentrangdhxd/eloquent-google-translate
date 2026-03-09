<?php

namespace TracyTran\EloquentTranslate\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use TracyTran\EloquentTranslate\Contracts\AIServiceContract;
use TracyTran\EloquentTranslate\Contracts\TranslationServiceContract;
use TracyTran\EloquentTranslate\EloquentTranslate;
use TracyTran\EloquentTranslate\Services\AITranslationService;
use TracyTran\EloquentTranslate\Commands\TranslateCommand;
use TracyTran\EloquentTranslate\Commands\DeleteTranslationCommand;

class TranslateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->eloquentTranslatePublishes();
        $this->eloquentTranslateConfig();
        $this->eloquentTranslateMigrations();
        $this->eloquentTranslateHelpers();
        $this->bootCommands();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // What we are trying to achieve here with this binding is to autodetect the locale sent from
        // the browser by checking if its present in the cookie or checking a specific locale.
        $this->app->bind('eloquent-translate', function () {
            return new EloquentTranslate();
        });

        $this->app->singleton(AIServiceContract::class, function ($app) {
            $driver = config('eloquent-translate.ai.driver');
            $drivers = config('eloquent-translate.ai.drivers', []);
            $serviceClass = data_get($drivers, "{$driver}.service");

            if (! is_string($serviceClass) || ! class_exists($serviceClass)) {
                throw new RuntimeException("Eloquent Translate AI driver [{$driver}] is not configured correctly.");
            }

            return $app->make($serviceClass);
        });

        $this->app->singleton(TranslationServiceContract::class, AITranslationService::class);
    }

    /**
     * Items to be published
     *
     * @return void
     */
    protected function eloquentTranslatePublishes()
    {
        $this->publishes([
            __DIR__ . '/../config/eloquent-translate.php' => config_path('eloquent-translate.php'),
        ], 'config');
    }

    /**
     * Marge the package's configuration.
     *
     * @return void
     */
    protected function eloquentTranslateConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/eloquent-translate.php',
            'eloquent-translate');
    }

    /**
     * Load migrations.
     *
     * @return void
     */
    protected function eloquentTranslateMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    }

    /**
     * Register helpers file
     *
     * @return void
     */
    protected function eloquentTranslateHelpers()
    {
        require_once __DIR__ . '/../helpers.php';
    }

    protected function bootCommands()
    {
        if ($this->app->runningInConsole()) {

            $this->commands([
                TranslateCommand::class,
                DeleteTranslationCommand::class,
            ]);
        }
    }
}
