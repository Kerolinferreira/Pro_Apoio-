<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação
|--------------------------------------------------------------------------
|
| Todas as rotas relacionadas a autenticação: registro, login, logout,
| recuperação de senha, etc.
|
*/

Route::prefix('auth')->group(function () {
    // Rotas de registro com rate limiting moderado (10 tentativas por minuto)
    // Permite correção de erros de validação sem bloqueio excessivo
    // Previne spam e criação massiva de contas falsas
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register/candidato', [AuthController::class, 'registerCandidato']);
        Route::post('/register/instituicao', [AuthController::class, 'registerInstituicao']);
    });

    // Rotas críticas com rate limiting agressivo (5 requisições por minuto)
    // Previne ataques de força bruta em credenciais
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Rota de login sem rate limiting
    Route::post('/login', [AuthController::class, 'login']);

    // Rotas de validação de duplicidade (rate limiting moderado para evitar abuso)
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/check-email', [AuthController::class, 'checkEmail']);
        Route::get('/check-cpf', [AuthController::class, 'checkCpf']);
        Route::get('/check-cnpj', [AuthController::class, 'checkCnpj']);
    });

    // Logout (requer autenticação)
    Route::middleware('jwt')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
