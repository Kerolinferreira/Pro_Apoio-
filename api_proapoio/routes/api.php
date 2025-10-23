<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidatoProfileController;
use App\Http\Controllers\InstituicaoProfileController;
use App\Http\Controllers\VagaController;
use App\Http\Controllers\VagaSalvaController;
use App\Http\Controllers\CandidatoFinderController;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ExternalApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aqui é onde você pode registrar rotas de API para sua aplicação. Essas
| rotas são carregadas pelo RouteServiceProvider e todas serão atribuídas
| ao grupo de middleware "api". Crie rotas para autenticação, perfis,
| vagas e propostas conforme definido no blueprint.
|
*/

// Rotas públicas de autenticação
// Prefixo /auth conforme especificado no contrato. Todas as rotas de
// autenticação devem começar com /auth para manter consistência.
Route::prefix('auth')->group(function () {
    Route::post('/register/candidato', [AuthController::class, 'registerCandidato']);
    Route::post('/register/instituicao', [AuthController::class, 'registerInstituicao']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Rotas protegidas por JWT
// Utiliza o middleware customizado 'jwt' para validar o token JWT e
// recuperar o usuário autenticado. O middleware deve ser registrado no Kernel.
Route::middleware('jwt')->group(function () {
    // Logout agora utiliza prefixo /auth para seguir o padrão de autenticação
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Perfil de candidato
    Route::get('/candidatos/me', [CandidatoProfileController::class, 'show']);
    Route::put('/candidatos/me', [CandidatoProfileController::class, 'update']);
    Route::post('/candidatos/me/foto', [CandidatoProfileController::class, 'uploadFoto']);
    Route::post('/candidatos/me/experiencias-profissionais', [CandidatoProfileController::class, 'storeExperienciaPro']);
    Route::delete('/candidatos/me/experiencias-profissionais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPro']);
    Route::post('/candidatos/me/experiencias-pessoais', [CandidatoProfileController::class, 'storeExperienciaPessoal']);
    Route::delete('/candidatos/me/experiencias-pessoais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPessoal']);
    Route::put('/candidatos/me/senha', [CandidatoProfileController::class, 'changePassword']);
    Route::delete('/candidatos/me', [CandidatoProfileController::class, 'deleteAccount']);

    // Perfil de instituição
    Route::get('/instituicoes/me', [InstituicaoProfileController::class, 'show']);
    Route::put('/instituicoes/me', [InstituicaoProfileController::class, 'update']);

    // Vagas (Instituição)
    Route::post('/vagas', [VagaController::class, 'store']);
    Route::get('/vagas/minhas', [VagaController::class, 'minhas']);
    // Detalhes de uma vaga da instituição logada
    // Utiliza rota /vagas/minhas/{id} para evitar conflito com a rota pública
    Route::get('/vagas/minhas/{id}', [VagaController::class, 'show'])->where('id','\\d+');
    Route::put('/vagas/{id}', [VagaController::class, 'update'])->where('id','\\d+');
    Route::put('/vagas/{id}/pausar', [VagaController::class, 'pause'])->where('id','\\d+');
    Route::put('/vagas/{id}/fechar', [VagaController::class, 'close'])->where('id','\\d+');

    // Vagas salvas (Candidato) – a rota foi realocada para /candidatos/me/vagas-salvas
    Route::get('/candidatos/me/vagas-salvas', [VagaSalvaController::class, 'index']);
    Route::post('/vagas/{id}/salvar', [VagaSalvaController::class, 'save'])->where('id','\\d+');
    Route::delete('/vagas/{id}/salvar', [VagaSalvaController::class, 'remove'])->where('id','\\d+');

    // Propostas: envio, listagem e ações
    Route::get('/propostas', [PropostaController::class, 'index']);
    Route::get('/propostas/{id}', [PropostaController::class, 'show'])->where('id','\\d+');
    Route::post('/propostas', [PropostaController::class, 'store']);
    Route::put('/propostas/{id}/aceitar', [PropostaController::class, 'accept'])->where('id','\\d+');
    Route::put('/propostas/{id}/recusar', [PropostaController::class, 'reject'])->where('id','\\d+');
    Route::delete('/propostas/{id}', [PropostaController::class, 'destroy'])->where('id','\\d+');

    // Notificações
    Route::get('/notificacoes', [NotificationController::class, 'index']);
    // Ação de marcar notificações como lidas precisa seguir o contrato e utilizar
    // o nome em português no endpoint. A implementação permanece a mesma.
    Route::post('/notificacoes/marcar-como-lidas', [NotificationController::class, 'markRead']);
});

// Rotas públicas de vagas (busca e detalhes)
Route::get('/vagas', [VagaController::class, 'index']);
Route::get('/vagas/{id}', [VagaController::class, 'showPublic'])->where('id','\\d+');

// Rotas públicas para APIs externas
Route::get('/external/viacep/{cep}', [ExternalApiController::class, 'viacep']);
Route::get('/external/receitaws/{cnpj}', [ExternalApiController::class, 'receitaws']);

// Rotas para busca de candidatos
// A listagem de candidatos é restrita a usuários autenticados do tipo
// instituição (middleware jwt garante autenticação; a verificação de tipo
// é feita no controlador). O endpoint público /candidatos/{id} retorna
// o perfil público de um candidato individual.
Route::middleware('jwt')->get('/candidatos', [CandidatoFinderController::class, 'index']);
Route::get('/candidatos/{id}', [CandidatoFinderController::class, 'show'])->where('id','\\d+');

// Rotas públicas para instituições: exibe informações básicas sem dados
// sensíveis. Utiliza o método showPublic do controller de instituição.
Route::get('/instituicoes/{id}', [InstituicaoProfileController::class, 'showPublic'])->where('id','\\d+');