<?php

namespace App\Providers;

use App\Models\PaymentProblem;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // The app is built on Bootstrap 4; Laravel's paginator defaults to Tailwind
        // markup, which renders unstyled here.
        Paginator::useBootstrapFour();

        // Unresolved payment problems are money that may still be owed, so the
        // count rides the admin sidebar rather than waiting to be looked for.
        // Counted per render, which is one cheap indexed query on an admin page.
        View::composer('layouts.dashboard_master', function ($view): void {
            $view->with(
                'sidebarProblemPaymentCount',
                auth()->check() && auth()->user()->role !== 'customer'
                    ? PaymentProblem::unresolved()->count()
                    : 0,
            );
        });
    }
}
