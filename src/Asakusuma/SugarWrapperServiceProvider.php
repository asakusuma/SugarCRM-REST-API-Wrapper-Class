<?php namespace Asakusuma\SugarWrapper;

use Illuminate\Support\ServiceProvider;

class SugarWrapperServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSugarWrapper();
    }

    /**
     * Register generate:model
     *
     * @return Commands\ModelGeneratorCommand
     */
    protected function registerSugarWrapper()
    {
        $this->app['SugarWrapper'] = $this->app->share(function($app)
        {
            return new Asakusuma\SugarWrapper\SugarRest;
        });
    }

}