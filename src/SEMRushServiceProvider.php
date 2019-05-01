<?php namespace AliasProject\SEMRush;

use Illuminate\Support\ServiceProvider;

class SEMRushServiceProvider extends ServiceProvider {

   /**
     * Bootstrap the application services.
     *
     * @return void
    */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/semrush.php' => config_path('semrush.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
    */
    public function register()
    {
        //
    }
}
