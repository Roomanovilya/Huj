<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\CashFlow;
use App\Observers\CashFlowObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CashFlow::observe(CashFlowObserver::class);
    }
}
