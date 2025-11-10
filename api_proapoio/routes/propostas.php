<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropostaController;

/*
|--------------------------------------------------------------------------
| Rotas de Propostas
|--------------------------------------------------------------------------
|
| Rotas relacionadas a propostas de trabalho entre candidatos e instituições.
|
*/

// Todas as rotas de propostas requerem autenticação
Route::middleware('jwt')->prefix('propostas')->name('propostas.')->group(function () {
    Route::get('/', [PropostaController::class, 'index'])->name('index');
    Route::get('/{id}', [PropostaController::class, 'show'])
        ->whereNumber('id')
        ->name('show');
    Route::put('/{id}/aceitar', [PropostaController::class, 'accept'])
        ->whereNumber('id')
        ->name('accept');
    Route::put('/{id}/recusar', [PropostaController::class, 'reject'])
        ->whereNumber('id')
        ->name('reject');
    Route::delete('/{id}', [PropostaController::class, 'destroy'])
        ->whereNumber('id')
        ->name('destroy');

    // Limita o envio de propostas: no máximo 10 por minuto por usuário autenticado
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [PropostaController::class, 'store'])->name('store');
    });
});
