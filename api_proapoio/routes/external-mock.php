<?php

/**
 * Rotas mockadas para APIs externas (desenvolvimento)
 * Use apenas quando APIs externas estiverem bloqueadas
 *
 * Para ativar, descomente as linhas em routes/api.php:
 * // require __DIR__.'/external-mock.php';
 */

use Illuminate\Support\Facades\Route;

Route::middleware('throttle:100,1')->prefix('external-mock')->name('external.mock.')->group(function () {

    // Mock ViaCEP
    Route::get('/viacep/{cep}', function (string $cep) {
        $cepClean = preg_replace('/\D+/', '', $cep);

        if (!preg_match('/^\d{8}$/', $cepClean)) {
            return response()->json(['message' => 'CEP inválido.'], 422);
        }

        // Dados mockados
        $mocks = [
            '01001000' => [
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'ibge' => '3550308',
                'gia' => '1004',
                'ddd' => '11',
                'siafi' => '7107'
            ],
            '20040020' => [
                'cep' => '20040-020',
                'logradouro' => 'Rua Primeiro de Março',
                'complemento' => '',
                'bairro' => 'Centro',
                'localidade' => 'Rio de Janeiro',
                'uf' => 'RJ',
                'ibge' => '3304557',
                'gia' => '',
                'ddd' => '21',
                'siafi' => '6001'
            ],
        ];

        if (isset($mocks[$cepClean])) {
            return response()->json($mocks[$cepClean]);
        }

        return response()->json(['erro' => true], 404);
    })->name('viacep');

    // Mock CNPJ
    Route::get('/receitaws/{cnpj}', function (string $cnpj) {
        $cnpjClean = preg_replace('/\D+/', '', $cnpj);

        if (!preg_match('/^\d{14}$/', $cnpjClean)) {
            return response()->json(['message' => 'CNPJ inválido.'], 422);
        }

        // Dados mockados
        $mocks = [
            '19131243000197' => [
                'cnpj' => '19.131.243/0001-97',
                'razao_social' => 'Empresa Exemplo LTDA',
                'nome_fantasia' => 'Exemplo',
                'email' => 'contato@exemplo.com',
                'telefone' => '(11) 3333-4444',
                'telefone_fixo' => '(11) 3333-4444',
                'logradouro' => 'Avenida Paulista',
                'numero' => '1000',
                'complemento' => 'Sala 10',
                'bairro' => 'Bela Vista',
                'cidade' => 'São Paulo',
                'municipio' => 'São Paulo',
                'estado' => 'SP',
                'uf' => 'SP',
                'cep' => '01310-100',
                'situacao' => 'ATIVA',
                'situacao_cadastral' => 'ATIVA',
                'data_situacao' => '01/01/2020',
            ],
        ];

        if (isset($mocks[$cnpjClean])) {
            return response()->json($mocks[$cnpjClean]);
        }

        return response()->json(['status' => 'ERROR', 'message' => 'CNPJ não encontrado.'], 404);
    })->name('receitaws');
});
