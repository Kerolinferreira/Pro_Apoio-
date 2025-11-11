<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExternalApiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DeficienciaController;

/*
|--------------------------------------------------------------------------
| Rotas de APIs Externas e Notificações
|--------------------------------------------------------------------------
|
| Rotas para integração com APIs externas e gerenciamento de notificações.
|
| APIs disponíveis:
| - ViaCEP: Consulta de endereço por CEP
| - CNPJ: Consulta de dados de empresa (BrasilAPI + ReceitaWS fallback)
|
*/

// APIs Externas (rate limiting balanceado: 100 requests por minuto)
Route::middleware('throttle:100,1')->prefix('external')->name('external.')->group(function () {
    // Consulta de CEP (ViaCEP)
    Route::get('/viacep/{cep}', [ExternalApiController::class, 'viacep'])->name('viacep');

    // Consulta de CNPJ (BrasilAPI com fallback para ReceitaWS)
    Route::get('/receitaws/{cnpj}', [ExternalApiController::class, 'receitaws'])->name('receitaws');
});

// Notificações (requer autenticação)
Route::middleware('jwt')->prefix('notificacoes')->name('notificacoes.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/count', [NotificationController::class, 'count'])->name('count');
    Route::post('/marcar-como-lidas', [NotificationController::class, 'markRead'])->name('markRead');
});

// Deficiências (listagem pública para cadastro)
Route::get('/deficiencias', [DeficienciaController::class, 'index'])->name('deficiencias.index');
