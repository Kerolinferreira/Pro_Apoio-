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

        $candidato = $user->candidato;

        if (!$candidato) {
            return response()->json([], 200);
        }

        $vagas = VagaSalva::where('id_candidato', $candidato->id)
            ->with('vaga.instituicao')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($vagas, 200);
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
