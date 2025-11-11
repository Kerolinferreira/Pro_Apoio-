<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vaga;
use App\Models\Proposta;
use App\Models\Candidato;
use App\Models\Instituicao;
use Illuminate\Support\Facades\DB;

/**
 * Controller para métricas do dashboard
 */
class DashboardController extends Controller
{
    /**
     * Retorna métricas do dashboard para candidatos
     * GET /dashboard/candidato
     */
    public function candidato(Request $request)
    {
        $user = $request->user();

        if (!$user || strtoupper($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $candidato = \App\Models\Candidato::where('id_usuario', $user->id)->first();

        if (!$candidato) {
            return response()->json(['message' => 'Candidato não encontrado.'], 404);
        }

        // Total de propostas enviadas
        $totalPropostas = Proposta::where('id_candidato', $candidato->id)->count();

        // Propostas por status
        $propostasPorStatus = Proposta::where('id_candidato', $candidato->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower($item->status) => $item->total];
            });

        // Vagas ativas disponíveis
        $vagasAtivas = Vaga::where('status', 'ATIVA')->count();

        // Propostas recentes (últimas 5)
        $propostasRecentes = Proposta::where('id_candidato', $candidato->id)
            ->with(['vaga.instituicao'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($proposta) {
                return [
                    'id' => $proposta->id_proposta,
                    'vaga_titulo' => $proposta->vaga->titulo_vaga ?? 'N/A',
                    'instituicao' => $proposta->vaga->instituicao->nome_fantasia ?? 'N/A',
                    'status' => strtolower($proposta->status),
                    'data_envio' => $proposta->data_envio?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'total_propostas' => $totalPropostas,
            'propostas_por_status' => [
                'enviada' => $propostasPorStatus['enviada'] ?? 0,
                'aceita' => $propostasPorStatus['aceita'] ?? 0,
                'recusada' => $propostasPorStatus['recusada'] ?? 0,
            ],
            'vagas_ativas' => $vagasAtivas,
            'propostas_recentes' => $propostasRecentes,
        ]);
    }

    /**
     * Retorna métricas do dashboard para instituições
     * GET /dashboard/instituicao
     */
    public function instituicao(Request $request)
    {
        $user = $request->user();

        if (!$user || strtoupper($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $instituicao = \App\Models\Instituicao::where('id_usuario', $user->id)->first();

        if (!$instituicao) {
            return response()->json(['message' => 'Instituição não encontrada.'], 404);
        }

        // Total de vagas criadas
        $totalVagas = Vaga::where('id_instituicao', $instituicao->id)->count();

        // Vagas por status
        $vagasPorStatus = Vaga::where('id_instituicao', $instituicao->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower($item->status) => $item->total];
            });

        // Total de propostas recebidas
        $totalPropostas = Proposta::whereHas('vaga', function ($query) use ($instituicao) {
            $query->where('id_instituicao', $instituicao->id);
        })->count();

        // Propostas por status
        $propostasPorStatus = Proposta::whereHas('vaga', function ($query) use ($instituicao) {
            $query->where('id_instituicao', $instituicao->id);
        })
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower($item->status) => $item->total];
            });

        // Propostas recentes (últimas 5)
        $propostasRecentes = Proposta::whereHas('vaga', function ($query) use ($instituicao) {
            $query->where('id_instituicao', $instituicao->id);
        })
            ->with(['vaga', 'candidato'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($proposta) {
                return [
                    'id' => $proposta->id_proposta,
                    'vaga_titulo' => $proposta->vaga->titulo_vaga ?? 'N/A',
                    'candidato_nome' => $proposta->candidato->nome_completo ?? 'N/A',
                    'status' => strtolower($proposta->status),
                    'data_envio' => $proposta->data_envio?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'total_vagas' => $totalVagas,
            'vagas_por_status' => [
                'ativa' => $vagasPorStatus['ativa'] ?? 0,
                'pausada' => $vagasPorStatus['pausada'] ?? 0,
                'fechada' => $vagasPorStatus['fechada'] ?? 0,
            ],
            'total_propostas' => $totalPropostas,
            'propostas_por_status' => [
                'enviada' => $propostasPorStatus['enviada'] ?? 0,
                'aceita' => $propostasPorStatus['aceita'] ?? 0,
                'recusada' => $propostasPorStatus['recusada'] ?? 0,
            ],
            'propostas_recentes' => $propostasRecentes,
        ]);
    }
}
