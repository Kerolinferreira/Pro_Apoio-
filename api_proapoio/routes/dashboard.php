<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Rotas de Dashboard
|--------------------------------------------------------------------------
|
| Endpoints para métricas e estatísticas do dashboard
|
*/

Route::middleware('jwt')->group(function () {
    Route::get('/dashboard/candidato', [DashboardController::class, 'candidato'])
        ->middleware('candidato')
        ->name('dashboard.candidato');

    Route::get('/dashboard/instituicao', [DashboardController::class, 'instituicao'])
        ->middleware('instituicao')
        ->name('dashboard.instituicao');
});
