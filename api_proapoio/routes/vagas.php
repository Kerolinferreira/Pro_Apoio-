<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VagaController;
use App\Http\Controllers\VagaSalvaController;

/*
|--------------------------------------------------------------------------
| Rotas de Vagas
|--------------------------------------------------------------------------
|
| Rotas relacionadas a vagas de trabalho: busca, criação, gerenciamento,
| e ações como salvar/remover vagas.
|
*/

// Rotas públicas
Route::get('/vagas', [VagaController::class, 'index'])->name('vagas.index');
Route::get('/vagas/{id}', [VagaController::class, 'showPublic'])
    ->whereNumber('id')
    ->name('vagas.show');

// Rotas protegidas (requer autenticação)
Route::middleware('jwt')->group(function () {

    // Criação e gerenciamento de vagas (SOMENTE Instituições)
    Route::middleware('instituicao')->group(function () {
        Route::post('/vagas', [VagaController::class, 'store'])->name('vagas.store');
        Route::get('/vagas/minhas', [VagaController::class, 'minhas'])->name('vagas.minhas');
        Route::get('/vagas/minhas/{id}', [VagaController::class, 'show'])
            ->whereNumber('id')
            ->name('vagas.minhas.show');
        Route::put('/vagas/{id}', [VagaController::class, 'update'])
            ->whereNumber('id')
            ->name('vagas.update');
        Route::put('/vagas/{id}/pausar', [VagaController::class, 'pause'])
            ->whereNumber('id')
            ->name('vagas.pause');
        Route::put('/vagas/{id}/fechar', [VagaController::class, 'close'])
            ->whereNumber('id')
            ->name('vagas.close');
        Route::patch('/vagas/{id}/status', [VagaController::class, 'changeStatus'])
            ->whereNumber('id')
            ->name('vagas.change-status');
        Route::delete('/vagas/{id}', [VagaController::class, 'destroy'])
            ->whereNumber('id')
            ->name('vagas.destroy');
    });

    // Ações em Vagas (Salvar/Remover - SOMENTE Candidatos)
    Route::middleware('candidato')->group(function () {
        Route::post('/vagas/{id}/salvar', [VagaSalvaController::class, 'salvar'])
            ->whereNumber('id')
            ->name('vagas.save');
        Route::delete('/vagas/{id}/remover', [VagaSalvaController::class, 'remover'])
            ->whereNumber('id')
            ->name('vagas.unsave');
    });
});
