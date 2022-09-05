<?php

namespace Sahiljb\OpenExchangeRates;

use Illuminate\Support\ServiceProvider;

class OpenExchangeRatesProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Sahiljb\OpenExchange\OpenExchangeRates' );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
