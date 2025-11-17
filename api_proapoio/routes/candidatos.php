<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidatoProfileController;
use App\Http\Controllers\CandidatoFinderController;
use App\Http\Controllers\VagaSalvaController;

/*
|--------------------------------------------------------------------------
| Rotas de Candidatos
|--------------------------------------------------------------------------
|
| Rotas relacionadas a perfis de candidatos, experiências profissionais,
| experiências pessoais, e busca de candidatos.
|
*/

// Rotas públicas
Route::get('/candidatos/{id}', [CandidatoFinderController::class, 'show'])
    ->whereNumber('id')
    ->name('candidatos.show');

// Rotas protegidas (requer autenticação)
Route::middleware('jwt')->group(function () {
    // Perfil do candidato autenticado - PADRÃO DOS TESTES (SOMENTE Candidatos)
    Route::middleware('candidato')->prefix('candidato/profile')->name('candidato.profile.')->group(function () {
        Route::get('/', [CandidatoProfileController::class, 'show'])->name('show');
        Route::put('/', [CandidatoProfileController::class, 'update'])->name('update');
        Route::post('/foto', [CandidatoProfileController::class, 'uploadFoto'])->name('foto');
        Route::put('/senha', [CandidatoProfileController::class, 'changePassword'])->name('senha');
        Route::delete('/', [CandidatoProfileController::class, 'deleteAccount'])->name('delete');

        // Experiências Profissionais
        Route::post('/experiencias-profissionais', [CandidatoProfileController::class, 'storeExperienciaPro'])
            ->name('experiencias-profissionais.store');
        Route::put('/experiencias-profissionais/{id}', [CandidatoProfileController::class, 'updateExperienciaPro'])
            ->whereNumber('id')
            ->name('experiencias-profissionais.update');
        Route::delete('/experiencias-profissionais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPro'])
            ->whereNumber('id')
            ->name('experiencias-profissionais.delete');

        // Experiências Pessoais
        Route::post('/experiencias-pessoais', [CandidatoProfileController::class, 'storeExperienciaPessoal'])
            ->name('experiencias-pessoais.store');
        Route::put('/experiencias-pessoais/{id}', [CandidatoProfileController::class, 'updateExperienciaPessoal'])
            ->whereNumber('id')
            ->name('experiencias-pessoais.update');
        Route::delete('/experiencias-pessoais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPessoal'])
            ->whereNumber('id')
            ->name('experiencias-pessoais.delete');
    });

    // Perfil do candidato autenticado - ROTAS ALTERNATIVAS (compatibilidade) (SOMENTE Candidatos)
    Route::middleware('candidato')->prefix('candidatos/me')->name('candidatos.me.')->group(function () {
        Route::get('/', [CandidatoProfileController::class, 'show'])->name('show');
        Route::put('/', [CandidatoProfileController::class, 'update'])->name('update');
        Route::post('/foto', [CandidatoProfileController::class, 'uploadFoto'])->name('foto');
        Route::put('/senha', [CandidatoProfileController::class, 'changePassword'])->name('senha');
        Route::delete('/', [CandidatoProfileController::class, 'deleteAccount'])->name('delete');

        // Experiências Profissionais
        Route::post('/experiencias-profissionais', [CandidatoProfileController::class, 'storeExperienciaPro'])
            ->name('experiencias-profissionais.store');
        Route::put('/experiencias-profissionais/{id}', [CandidatoProfileController::class, 'updateExperienciaPro'])
            ->whereNumber('id')
            ->name('experiencias-profissionais.update');
        Route::delete('/experiencias-profissionais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPro'])
            ->whereNumber('id')
            ->name('experiencias-profissionais.delete');

        // Experiências Pessoais
        Route::post('/experiencias-pessoais', [CandidatoProfileController::class, 'storeExperienciaPessoal'])
            ->name('experiencias-pessoais.store');
        Route::put('/experiencias-pessoais/{id}', [CandidatoProfileController::class, 'updateExperienciaPessoal'])
            ->whereNumber('id')
            ->name('experiencias-pessoais.update');
        Route::delete('/experiencias-pessoais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPessoal'])
            ->whereNumber('id')
            ->name('experiencias-pessoais.delete');

        // Vagas Salvas
        Route::get('/vagas-salvas', [VagaSalvaController::class, 'index'])->name('vagas-salvas');
    });

    // Busca de Candidatos (SOMENTE Instituições)
    Route::get('/candidatos', [CandidatoFinderController::class, 'buscar'])->name('candidatos.buscar');
});
