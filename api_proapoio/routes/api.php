<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Este arquivo organiza as rotas da API importando arquivos modulares.
| O middleware 'api' é aplicado automaticamente pelo RouteServiceProvider.
|
| Estrutura de Rotas:
| - auth.php: Autenticação (registro, login, logout, recuperação de senha)
| - candidatos.php: Perfis de candidatos, experiências, busca
| - instituicoes.php: Perfis de instituições
| - vagas.php: Vagas de trabalho (busca, criação, gerenciamento)
| - propostas.php: Propostas entre candidatos e instituições
| - external.php: APIs externas (ViaCEP, ReceitaWS) e notificações
|
*/

// Rotas de Autenticação
require __DIR__.'/auth.php';

// Rotas de Candidatos
require __DIR__.'/candidatos.php';

// Rotas de Instituições
require __DIR__.'/instituicoes.php';

// Rotas de Vagas
require __DIR__.'/vagas.php';

// Rotas de Propostas
require __DIR__.'/propostas.php';

// APIs Externas e Notificações
require __DIR__.'/external.php';

// Dashboard
require __DIR__.'/dashboard.php';
