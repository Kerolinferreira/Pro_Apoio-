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
| Este arquivo contém todas as rotas da API.
| O middleware 'api' é aplicado automaticamente pelo RouteServiceProvider.
|
*/

// --- 1. ROTAS PÚBLICAS DE AUTENTICAÇÃO ---
// Prefixo /auth para organização
Route::prefix('auth')->group(function () {
    Route::post('/register/candidato', [AuthController::class, 'registerCandidato']);
    Route::post('/register/instituicao', [AuthController::class, 'registerInstituicao']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// --- 2. ROTAS PÚBLICAS GERAIS ---

// Vagas (Busca e Detalhes)
Route::get('/vagas', [VagaController::class, 'index']);
Route::get('/vagas/{id}', [VagaController::class, 'showPublic'])->whereNumber('id');

// Perfis Públicos (Candidato e Instituição)
Route::get('/candidatos/{id}', [CandidatoFinderController::class, 'show'])->whereNumber('id');
Route::get('/instituicoes/{id}', [InstituicaoProfileController::class, 'showPublic'])->whereNumber('id');

// APIs Externas
Route::get('/external/viacep/{cep}', [ExternalApiController::class, 'viacep']);
Route::get('/external/receitaws/{cnpj}', [ExternalApiController::class, 'receitaws']);


// --- 3. ROTAS PROTEGIDAS POR JWT ---
// O uso de 'auth:api' garante que o token JWT seja validado.
Route::middleware('auth:api')->group(function () {

    // Rota para obter dados do usuário autenticado (Usada no AuthContext para 'hydrate')
    Route::get('/profile/me', [AuthController::class, 'me']);
    
    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Perfil de Candidato
    Route::prefix('candidatos/me')->group(function () {
        Route::get('/', [CandidatoProfileController::class, 'show']);
        Route::put('/', [CandidatoProfileController::class, 'update']);
        Route::post('/foto', [CandidatoProfileController::class, 'uploadFoto']);
        Route::post('/experiencias-profissionais', [CandidatoProfileController::class, 'storeExperienciaPro']);
        Route::delete('/experiencias-profissionais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPro'])->whereNumber('id');
        Route::post('/experiencias-pessoais', [CandidatoProfileController::class, 'storeExperienciaPessoal']);
        Route::delete('/experiencias-pessoais/{id}', [CandidatoProfileController::class, 'deleteExperienciaPessoal'])->whereNumber('id');
        Route::put('/senha', [CandidatoProfileController::class, 'changePassword']);
        Route::delete('/', [CandidatoProfileController::class, 'deleteAccount']);
        
        // Vagas Salvas (reorganizado sob o prefixo do Candidato)
        Route::get('/vagas-salvas', [VagaSalvaController::class, 'index']);
    });

    // Perfil de Instituição
    Route::prefix('instituicoes/me')->group(function () {
        Route::get('/', [InstituicaoProfileController::class, 'show']);
        Route::put('/', [InstituicaoProfileController::class, 'update']);
        Route::post('/logo', [InstituicaoProfileController::class, 'uploadLogo']); // ADICIONADO
        Route::put('/senha', [InstituicaoProfileController::class, 'changePassword']); // ADICIONADO
    });

    // Vagas (Criação e Gerenciamento pela Instituição)
    Route::post('/vagas', [VagaController::class, 'store']);
    Route::get('/vagas/minhas', [VagaController::class, 'minhas']);
    Route::get('/vagas/minhas/{id}', [VagaController::class, 'show'])->whereNumber('id');
    Route::put('/vagas/{id}', [VagaController::class, 'update'])->whereNumber('id');
    Route::put('/vagas/{id}/pausar', [VagaController::class, 'pause'])->whereNumber('id');
    Route::put('/vagas/{id}/fechar', [VagaController::class, 'close'])->whereNumber('id');

    // Ações em Vagas (Salvar/Remover)
    Route::post('/vagas/{id}/salvar', [VagaSalvaController::class, 'save'])->whereNumber('id');
    Route::delete('/vagas/{id}/salvar', [VagaSalvaController::class, 'remove'])->whereNumber('id');

    // Propostas
    Route::prefix('propostas')->group(function () {
        Route::get('/', [PropostaController::class, 'index']);
        Route::post('/', [PropostaController::class, 'store']); // Enviar proposta
        Route::get('/{id}', [PropostaController::class, 'show'])->whereNumber('id');
        Route::put('/{id}/aceitar', [PropostaController::class, 'accept'])->whereNumber('id');
        Route::put('/{id}/recusar', [PropostaController::class, 'reject'])->whereNumber('id');
        Route::delete('/{id}', [PropostaController::class, 'destroy'])->whereNumber('id'); // Cancelar proposta
    });

    // Notificações
    Route::get('/notificacoes', [NotificationController::class, 'index']);
    Route::post('/notificacoes/marcar-como-lidas', [NotificationController::class, 'markRead']);

    // Busca de Candidatos (Apenas para Instituições Autenticadas)
    Route::get('/candidatos', [CandidatoFinderController::class, 'buscar']);
});
