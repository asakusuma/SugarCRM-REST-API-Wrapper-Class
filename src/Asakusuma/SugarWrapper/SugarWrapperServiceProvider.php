<?php namespace Asakusuma\SugarWrapper;

use Illuminate\Support\ServiceProvider;

class SugarWrapperServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->bind('sugar', function()
        {
            $sugar = new Sugar;

            $sugar->setUrl('http://sugarcrm/service/v2/rest.php');
            $sugar->setUsername('RestUser');
            $sugar->setPassword('password');

            $sugar->connect();

            return $sugar;
        });
    }

}