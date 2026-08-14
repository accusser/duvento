<?php

namespace App\Providers;

use App\Contracts\BillingGateway;
use App\Support\LocalBillingGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists(\Duvento\Cloud\CloudServiceProvider::class)) {
            $this->app->bind(BillingGateway::class, LocalBillingGateway::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
