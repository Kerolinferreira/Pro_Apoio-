<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstituicaoProfileController;

/*
|--------------------------------------------------------------------------
| Rotas de Instituições
|--------------------------------------------------------------------------
|
| Rotas relacionadas a perfis de instituições de ensino.
|
*/

// Rotas públicas
Route::get('/instituicoes/{id}', [InstituicaoProfileController::class, 'showPublic'])
    ->whereNumber('id')
    ->name('instituicoes.show');

// Rotas protegidas (requer autenticação)
Route::middleware(['jwt', 'instituicao'])->group(function () {
    // Perfil da instituição autenticada - PADRÃO DOS TESTES (SOMENTE Instituições)
    Route::prefix('instituicao/profile')->name('instituicao.profile.')->group(function () {
        Route::get('/', [InstituicaoProfileController::class, 'show'])->name('show');
        Route::put('/', [InstituicaoProfileController::class, 'update'])->name('update');
        Route::post('/logo', [InstituicaoProfileController::class, 'uploadLogo'])->name('logo');
        Route::put('/senha', [InstituicaoProfileController::class, 'changePassword'])->name('senha');
        Route::delete('/', [InstituicaoProfileController::class, 'deleteAccount'])->name('delete');
    });

    // Perfil da instituição autenticada - ROTAS ALTERNATIVAS (compatibilidade) (SOMENTE Instituições)
    Route::prefix('instituicoes/me')->name('instituicoes.me.')->group(function () {
        Route::get('/', [InstituicaoProfileController::class, 'show'])->name('show');
        Route::put('/', [InstituicaoProfileController::class, 'update'])->name('update');
        Route::post('/logo', [InstituicaoProfileController::class, 'uploadLogo'])->name('logo');
        Route::put('/senha', [InstituicaoProfileController::class, 'changePassword'])->name('senha');
        Route::delete('/', [InstituicaoProfileController::class, 'deleteAccount'])->name('delete');
    });
});
