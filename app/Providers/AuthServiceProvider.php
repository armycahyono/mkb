<?php

namespace App\Providers;

use App\Models\PengajuanBarang;
use App\Policies\PengajuanBarangPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        PengajuanBarang::class => PengajuanBarangPolicy::class,
    ];

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
        $this->registerPolicies();
        // Additional gates can be defined here if needed.
    }
}
