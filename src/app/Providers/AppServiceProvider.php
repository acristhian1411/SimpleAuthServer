<?php

namespace App\Providers;

use App\Models\AppClient;
use App\Passport\BearerTokenResponse;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Relation::enforceMorphMap([
            'user' => 'App\\Models\\User',
        ]);

        Passport::useClientModel(AppClient::class);

        Passport::useAuthorizationServerResponseType(new BearerTokenResponse);

        Passport::enablePasswordGrant();

        Passport::tokensCan([
            'profile:read' => 'Leer perfil del usuario autenticado.',
            'expenses:read' => 'Leer gastos del usuario.',
            'expenses:write' => 'Crear y sincronizar gastos del usuario.',
        ]);

        Passport::setDefaultScope([
            'profile:read',
            'expenses:read',
            'expenses:write',
        ]);

        Passport::tokensExpireIn(now()->addDays((int) env('PASSPORT_ACCESS_TOKEN_TTL_DAYS', 15)));
        Passport::refreshTokensExpireIn(now()->addDays((int) env('PASSPORT_REFRESH_TOKEN_TTL_DAYS', 30)));
    }
}
