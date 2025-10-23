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
     * Proxy para ReceitaWS (consulta CNPJ).
     * Valida CNPJ (14 dígitos), usa timeout e cache de 24h.
     * Rota sugerida: GET /external/receitaws/{cnpj}
     */
    public function receitaws(Request $request, string $cnpj)
    {
        $cnpjClean = preg_replace('/\D+/', '', $cnpj);
        if (!preg_match('/^\d{14}$/', $cnpjClean)) {
            return response()->json(['message' => 'CNPJ inválido.'], 422);
        }

        $cacheKey = "ext:receitaws:{$cnpjClean}";
        $bypass   = (bool) $request->boolean('fresh', false);

        if (!$bypass && Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        try {
            $json = Http::timeout(10)
                ->acceptJson()
                ->withHeaders([
                    // Alguns provedores exigem um User-Agent válido
                    'User-Agent' => 'ProApoio/1.0 (+https://example.com)',
                ])
                ->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjClean}")
                ->throw()
                ->json();

            // Normaliza chaves comuns para o front (quando existirem)
            $normalized = $json;
            $normalized['razao_social']  = $json['razao_social']  ?? ($json['nome']      ?? null);
            $normalized['nome_fantasia'] = $json['nome_fantasia'] ?? ($json['fantasia']  ?? null);
            $normalized['cnpj']          = $cnpjClean;

            Cache::put($cacheKey, $normalized, now()->addDay());
            return response()->json($normalized);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erro ao consultar ReceitaWS.'], 502);
        }
    }
}
