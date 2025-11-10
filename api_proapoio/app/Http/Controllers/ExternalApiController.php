<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExternalApiController extends Controller
{
    /**
     * Proxy para ViaCEP.
     * Valida CEP (8 dígitos), usa timeout e cache de 12h.
     * Rota sugerida: GET /external/viacep/{cep}
     */
    public function viacep(Request $request, string $cep)
    {
        $cepClean = preg_replace('/\D+/', '', $cep);
        if (!preg_match('/^\d{8}$/', $cepClean)) {
            return response()->json(['message' => 'CEP inválido.'], 422);
        }

        $cacheKey = "ext:viacep:{$cepClean}";
        $bypass   = (bool) $request->boolean('fresh', false);

        if (!$bypass && Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        try {
            $json = Http::timeout(5)
                ->withOptions(['verify' => false])
                ->acceptJson()
                ->get("https://viacep.com.br/ws/{$cepClean}/json/")
                ->throw()
                ->json();

            // ViaCEP usa { "erro": true } para CEP inexistente
            if (isset($json['erro']) && $json['erro'] === true) {
                return response()->json(['message' => 'CEP não encontrado.'], 404);
            }

            Cache::put($cacheKey, $json, now()->addHours(12));
            return response()->json($json);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erro ao consultar ViaCEP.'], 502);
        }
    }

    /**
     * Proxy para consulta de CNPJ (com fallback).
     * Tenta BrasilAPI primeiro, depois ReceitaWS.
     * Valida CNPJ (14 dígitos), usa timeout e cache de 24h.
     * Rota sugerida: GET /external/receitaws/{cnpj}
     */
    public function receitaws(Request $request, string $cnpj)
    {
        $cnpjClean = preg_replace('/\D+/', '', $cnpj);
        if (!preg_match('/^\d{14}$/', $cnpjClean)) {
            return response()->json(['message' => 'CNPJ inválido. Deve conter 14 dígitos.'], 422);
        }

        $cacheKey = "ext:cnpj:{$cnpjClean}";
        $bypass   = (bool) $request->boolean('fresh', false);

        if (!$bypass && Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        // Tenta BrasilAPI primeiro (mais rápida e confiável)
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->acceptJson()
                ->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpjClean}");

            if ($response->successful()) {
                $data = $response->json();

                // Verifica se encontrou a empresa
                if (!isset($data['message']) && !isset($data['type'])) {
                    $normalized = $this->normalizeBrasilApi($data, $cnpjClean);
                    Cache::put($cacheKey, $normalized, now()->addDay());
                    return response()->json($normalized);
                }
            }
        } catch (\Throwable $e) {
            // Continua para o fallback
        }

        // Fallback: ReceitaWS
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'ProApoio/1.0 (+https://proapoio.com)',
                ])
                ->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjClean}");

            if ($response->failed()) {
                return response()->json(['message' => 'CNPJ não encontrado.'], 404);
            }

            $data = $response->json();

            // ReceitaWS retorna status ERROR quando não encontra
            if (isset($data['status']) && $data['status'] === 'ERROR') {
                return response()->json([
                    'message' => $data['message'] ?? 'CNPJ não encontrado.',
                ], 404);
            }

            $normalized = $this->normalizeReceitaWs($data, $cnpjClean);
            Cache::put($cacheKey, $normalized, now()->addDay());
            return response()->json($normalized);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Não foi possível consultar o CNPJ. Tente novamente mais tarde.',
            ], 502);
        }
    }

    /**
     * Normaliza resposta da BrasilAPI para formato padrão.
     */
    private function normalizeBrasilApi(array $data, string $cnpj): array
    {
        $estabelecimento = $data['estabelecimento'] ?? [];
        $cidade = $estabelecimento['cidade'] ?? [];
        $estado = $estabelecimento['estado'] ?? [];

        // Formata telefone
        $telefone = null;
        if (!empty($estabelecimento['ddd1']) && !empty($estabelecimento['telefone1'])) {
            $ddd = $estabelecimento['ddd1'];
            $tel = $estabelecimento['telefone1'];
            $telefone = "({$ddd}) {$tel}";
        }

        return [
            'cnpj' => $cnpj,
            'razao_social' => $data['razao_social'] ?? '',
            'nome_fantasia' => $estabelecimento['nome_fantasia'] ?? '',
            'email' => $estabelecimento['email'] ?? null,
            'telefone' => $telefone,
            'telefone_fixo' => $telefone,
            'logradouro' => $estabelecimento['logradouro'] ?? null,
            'numero' => $estabelecimento['numero'] ?? null,
            'complemento' => $estabelecimento['complemento'] ?? null,
            'bairro' => $estabelecimento['bairro'] ?? null,
            'cidade' => $cidade['nome'] ?? null,
            'municipio' => $cidade['nome'] ?? null,
            'estado' => $estado['sigla'] ?? null,
            'uf' => $estado['sigla'] ?? null,
            'cep' => $estabelecimento['cep'] ?? null,
            'situacao' => $estabelecimento['situacao_cadastral'] ?? null,
            'situacao_cadastral' => $estabelecimento['situacao_cadastral'] ?? null,
            'data_situacao' => $estabelecimento['data_situacao_cadastral'] ?? null,
        ];
    }

    /**
     * Normaliza resposta da ReceitaWS para formato padrão.
     */
    private function normalizeReceitaWs(array $data, string $cnpj): array
    {
        return [
            'cnpj' => $cnpj,
            'razao_social' => $data['nome'] ?? '',
            'nome_fantasia' => $data['fantasia'] ?? '',
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'telefone_fixo' => $data['telefone'] ?? null,
            'logradouro' => $data['logradouro'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['municipio'] ?? null,
            'municipio' => $data['municipio'] ?? null,
            'estado' => $data['uf'] ?? null,
            'uf' => $data['uf'] ?? null,
            'cep' => $data['cep'] ?? null,
            'situacao' => $data['situacao'] ?? null,
            'situacao_cadastral' => $data['situacao'] ?? null,
            'data_situacao' => $data['data_situacao'] ?? null,
        ];
    }
}
