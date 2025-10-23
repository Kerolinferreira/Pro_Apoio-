<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidato;

/**
 * Busca pública de candidatos para instituições.
 * Retorna apenas campos não sensíveis.
 */
class CandidatoFinderController extends Controller
{
    /**
     * GET /candidatos
     * Filtros aceitos: q, cidade, escolaridade
     * Paginação: ?page=1&per_page=10
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Apenas instituição autenticada pode listar
        if (!$user || strtolower($user->tipo_usuario ?? '') !== 'instituicao') {
            return response()->json(['message' => 'Acesso restrito a instituições.'], 403);
        }

        $perPage = (int) $request->integer('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $query = Candidato::query()->with('endereco');

        // q: busca por nome
        if ($q = trim((string) $request->input('q'))) {
            $query->where('nome_completo', 'like', "%{$q}%");
        }

        // cidade: compara por igualdade ou like case-insensitive
        if ($cidade = trim((string) $request->input('cidade'))) {
            $query->whereHas('endereco', function ($sub) use ($cidade) {
                $sub->where('cidade', 'like', $cidade);
            });
        }

        // escolaridade do front -> coluna nivel_escolaridade
        if ($esc = trim((string) $request->input('escolaridade'))) {
            $query->where('nivel_escolaridade', $esc);
        }

        // Ordenação simples por nome
        $query->orderBy('nome_completo');

        $paginado = $query->paginate($perPage)->appends($request->query());

        // Mapear para campos esperados pelo front, sem dados sensíveis
        $paginado->getCollection()->transform(function ($c) {
            return [
                'id'                 => $c->id,
                'nome'               => $c->nome_completo,
                'cidade'             => optional($c->endereco)->cidade,
                // Nomes alinhados ao front:
                'escolaridade'       => $c->nivel_escolaridade,
                'nome_curso'         => $c->curso_superior,
                'nome_instituicao_ensino' => $c->instituicao_ensino,
                'status'             => $c->status,
            ];
        });

        return response()->json($paginado);
    }

    /**
     * GET /candidatos/{id}
     * Perfil público sem contatos/CPF.
     */
    public function show($id)
    {
        $c = Candidato::with('endereco')->findOrFail($id);

        return response()->json([
            'id'                       => $c->id,
            'nome'                     => $c->nome_completo,
            'cidade'                   => optional($c->endereco)->cidade,
            'escolaridade'             => $c->nivel_escolaridade,
            'nome_curso'               => $c->curso_superior,
            'nome_instituicao_ensino'  => $c->instituicao_ensino,
            'status'                   => $c->status,
        ]);
    }
}
