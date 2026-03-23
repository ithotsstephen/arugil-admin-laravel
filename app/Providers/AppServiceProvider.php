<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\ErrorSolutions\Contracts\SolutionProviderRepository as SolutionProviderRepositoryContract;
use Spatie\ErrorSolutions\SolutionProviderRepository;
use Spatie\FlareClient\Support\Container as FlareContainer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Provide a fallback binding for Spatie's SolutionProviderRepository contract
        // so the error/solution tooling can resolve the contract even if the
        // package service provider was not auto-discovered or registered.
        if (class_exists(SolutionProviderRepository::class)
            && class_exists(SolutionProviderRepositoryContract::class)
            && ! $this->app->has(SolutionProviderRepositoryContract::class)) {
            $this->app->singleton(SolutionProviderRepositoryContract::class, function () {
                return new SolutionProviderRepository();
            });
        }

        // Also register the solution provider in Spatie's Flare container so
        // packages that use the Flare container (instead of the Laravel
        // container) can resolve the SolutionProviderRepository contract.
        if (class_exists(FlareContainer::class)) {
            $flare = FlareContainer::instance();
            if (! $flare->has(SolutionProviderRepositoryContract::class)) {
                $flare->singleton(SolutionProviderRepositoryContract::class, function () {
                    return new SolutionProviderRepository();
                });
            }
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
