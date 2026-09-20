<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        if (! $this->app->runningInConsole()) {
            $forwardedHost = request()->header('x-forwarded-host');
            $forwardedProto = request()->header('x-forwarded-proto');

            if ($forwardedHost && str_contains($forwardedHost, 'sudahonline.my.id')) {
                $proto = $forwardedProto ?: 'https';
                URL::forceRootUrl("{$proto}://{$forwardedHost}");
                if ($proto === 'https') {
                    URL::forceScheme('https');
                }
            } else {
                $host = request()->getHost();
                $isLocalHost = in_array($host, ['localhost', '127.0.0.1', 'mkb.test'], true)
                    || str_ends_with($host, '.test')
                    || str_ends_with($host, '.local');

                if (! $isLocalHost && (request()->isSecure() || $forwardedProto === 'https' || str_contains($host, 'sudahonline.my.id'))) {
                    URL::forceScheme('https');
                } elseif ($isLocalHost && ! request()->isSecure()) {
                    URL::forceScheme('http');
                }
            }
        }

        // Definisi Hak Akses / Gates untuk RBAC
        Gate::define('access-data-pokok', fn (User $user) => $user->canAccessDataPokok());
        Gate::define('access-bosp', fn (User $user) => $user->canAccessBosp());
        Gate::define('access-bosp-kas', fn (User $user) => $user->canAccessBospKas());
        Gate::define('access-bpopp', fn (User $user) => $user->canAccessBpopp());
        Gate::define('access-bpopp-kas', fn (User $user) => $user->canAccessBpoppKas());
        Gate::define('manage-settings', fn (User $user) => $user->canManageSettings());
    }
}
