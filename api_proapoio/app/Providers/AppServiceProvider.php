<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Proposta;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Observers\PropostaObserver;
use App\Guards\JwtGuard;

/**
 * Service provider para registrar observers e outras bootstraps.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar serviços do aplicativo.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap de serviços.
     */
    public function boot(): void
    {
        // Registra o morph map para relacionamentos polimórficos
        Relation::morphMap([
            'Candidato' => Candidato::class,
            'Instituicao' => Instituicao::class,
        ]);

        // Registra o observer da Proposta
        Proposta::observe(PropostaObserver::class);

        // Registra o guard driver customizado 'jwt'
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                Auth::createUserProvider($config['provider']),
                $app->make('request')
            );
        });
    }
}