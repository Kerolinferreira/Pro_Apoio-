<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VagaSalva;
use App\Models\Vaga;

class VagaSalvaController extends Controller
{
    /** Lista todas as vagas salvas do candidato logado. */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
                return response()->json(['message' => 'Apenas candidatos.'], 403);
            }

            $candidato = $user->candidato;

            if (!$candidato) {
                return response()->json([], 200);
            }

            // Busca as vagas salvas com eager loading
            $vagasSalvas = VagaSalva::where('id_candidato', $candidato->id_candidato)
                ->with(['vaga' => function ($query) {
                    $query->with('instituicao');
                }])
                ->orderByDesc('created_at')
                ->get();

            // Formata a resposta para retornar apenas os dados das vagas
            $vagas = $vagasSalvas->map(function ($vagaSalva) {
                if (!$vagaSalva->vaga) {
                    \Log::warning('VagaSalva sem vaga associada', ['id' => $vagaSalva->id, 'id_vaga' => $vagaSalva->id_vaga]);
                    return null;
                }

                $vaga = $vagaSalva->vaga;

                return [
                    'id' => $vaga->id,
                    'titulo' => $vaga->titulo ?? $vaga->titulo_vaga ?? 'Sem título',
                    'cidade' => $vaga->cidade ?? 'Não informado',
                    'estado' => $vaga->estado ?? 'SP',
                    'regime_contratacao' => $vaga->regime_contratacao ?? 'Não informado',
                    'modalidade' => $vaga->modalidade,
                    'tipo' => $vaga->tipo,
                    'instituicao' => [
                        'id' => $vaga->instituicao->id_instituicao ?? null,
                        'nome_fantasia' => $vaga->instituicao->nome_fantasia ?? 'Não informado',
                    ],
                ];
            })->filter()->values(); // Remove nulls e reindex

            return response()->json($vagas, 200);

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar vagas salvas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Erro ao buscar vagas salvas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /** Salva uma vaga para o candidato logado. */
    public function salvar(Request $request, $id)
    {
        $user = $request->user();
        if (strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return response()->json(['message' => 'Apenas candidatos.'], 403);
        }

        $vaga = Vaga::where('id_vaga', $id)
            ->where('status', 'ativa')
            ->first();

        if (!$vaga) {
            return response()->json(['message' => 'Vaga não encontrada.'], 404);
        }

        $candidato = $user->candidato;

        $salva = VagaSalva::firstOrCreate([
            'id_candidato' => $candidato->id,
            'id_vaga' => $id,
        ]);

        return response()->json($salva->load('vaga'), 201);
    }

    /** Remove uma vaga salva. */
    public function remover(Request $request, $id)
    {
        $user = $request->user();
        if (strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return response()->json(['message' => 'Apenas candidatos.'], 403);
        }

        $candidato = $user->candidato;

        $vagaSalva = VagaSalva::where('id_vaga', $id)
            ->where('id_candidato', $candidato->id)
            ->first();

        if (!$vagaSalva) {
            return response()->json(['message' => 'Vaga não encontrada.'], 404);
        }

        $vagaSalva->delete();

        return response()->json(['message' => 'Vaga removida dos favoritos.'], 200);
    }
}
