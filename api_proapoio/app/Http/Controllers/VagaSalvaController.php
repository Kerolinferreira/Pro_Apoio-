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
        $user = $request->user();
        if (strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return response()->json(['message' => 'Apenas candidatos.'], 403);
        }

        $candidatoId = optional($user->candidato)->id ?? 0;

        $lista = VagaSalva::where('id_candidato', $candidatoId)
            ->with(['vaga.instituicao'])
            ->orderByDesc('id')
            ->get();

        // Mapeia para o formato esperado pelo front:
        // { id, vaga: { id, titulo_vaga, cidade, regime_contratacao, instituicao: { nome_fantasia } } }
        $data = $lista->map(function ($item) {
            $v = $item->vaga;
            return [
                'id'   => $item->id,
                'vaga' => $v ? [
                    'id'                  => $v->id_vaga, // normaliza id
                    'titulo_vaga'         => $v->titulo_vaga,
                    'cidade'              => $v->cidade,
                    'regime_contratacao'  => $v->regime_contratacao,
                    'instituicao'         => [
                        'nome_fantasia' => optional($v->instituicao)->nome_fantasia,
                    ],
                ] : null,
            ];
        });

        return response()->json($data);
    }

    /** Salva uma vaga para o candidato logado. */
    public function save(Request $request, $id)
    {
        $user = $request->user();
        if (strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return response()->json(['message' => 'Apenas candidatos.'], 403);
        }

        $candidatoId = optional($user->candidato)->id ?? 0;

        $vaga = Vaga::where('id_vaga', $id)
            ->where('status', 'ABERTA')
            ->firstOrFail();

        $saved = VagaSalva::firstOrCreate([
            'id_candidato' => $candidatoId,
            // atenção: chave correta é id_vaga (não $vaga->id)
            'id_vaga'      => $vaga->id_vaga,
        ]);

        return response()->json($saved->load('vaga'), 201);
    }

    /** Remove uma vaga salva. */
    public function remove(Request $request, $id)
    {
        $user = $request->user();
        if (strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return response()->json(['message' => 'Apenas candidatos.'], 403);
        }

        $candidatoId = optional($user->candidato)->id ?? 0;

        $saved = VagaSalva::where('id_candidato', $candidatoId)
            ->where('id_vaga', $id) // o front envia vaga.id (== id_vaga)
            ->firstOrFail();

        $saved->delete();

        return response()->json(['message' => 'Vaga removida dos favoritos.']);
    }
}
