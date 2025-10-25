<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Proposta;
use App\Observers\PropostaObserver;

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
        // Registra o observer da Proposta
        Proposta::observe(PropostaObserver::class);
    }
}