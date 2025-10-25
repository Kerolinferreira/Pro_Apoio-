<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Proposta;
use App\Models\Vaga;

class PropostaController extends Controller
{
    /**
     * GET /propostas?tipo=enviadas|recebidas&per_page=10
     * Candidato: enviadas = iniciador CANDIDATO; recebidas = INSTITUICAO.
     * Instituição: filtra por vagas da instituição.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tipo = $request->input('tipo', 'enviadas'); // padrão

        $perPage = $this->safePerPage($request, 10);

        if (($user->tipo_usuario ?? '') === 'CANDIDATO') {
            $candidatoId = optional($user->candidato)->id ?? 0;

            $q = Proposta::with(['vaga.instituicao', 'candidato'])
                ->where('id_candidato', $candidatoId)
                ->when($tipo === 'recebidas',
                    fn($qq) => $qq->where('iniciador', 'INSTITUICAO'),
                    fn($qq) => $qq->where('iniciador', 'CANDIDATO')
                )
                ->latest();

            return $q->paginate($perPage)->appends($request->query());
        }

        // INSTITUICAO
        $instituicaoId = optional($user->instituicao)->id ?? 0;
        $vagaIds = Vaga::where('id_instituicao', $instituicaoId)->pluck('id_vaga');

        $q = Proposta::with(['vaga.instituicao', 'candidato'])
            ->whereIn('id_vaga', $vagaIds)
            ->when($tipo === 'recebidas',
                fn($qq) => $qq->where('iniciador', 'CANDIDATO'),
                fn($qq) => $qq->where('iniciador', 'INSTITUICAO')
            )
            ->latest();

        return $q->paginate($perPage)->appends($request->query());
    }

    /**
     * GET /propostas/{id}
     * Se status = ACEITA, inclui contatos da outra parte em "contatos".
     */
    public function show($id, Request $request)
    {
        $user = $request->user();

        $proposta = Proposta::with([
            'vaga.instituicao.user',
            'candidato.user',
        ])->findOrFail($id);

        // autorização básica: precisa ser candidato envolvido OU instituição dona da vaga
        $isCandidato = ($user->tipo_usuario ?? '') === 'CANDIDATO'
            && optional($user->candidato)->id === $proposta->id_candidato;

        $isInstituicao = ($user->tipo_usuario ?? '') === 'INSTITUICAO'
            && optional($user->instituicao)->id === optional($proposta->vaga)->id_instituicao;

        if (!$isCandidato && !$isInstituicao) {
            return $this->forbidden();
        }

        $data = $proposta->toArray();

        // contatos apenas quando ACEITA
        if ($proposta->status === 'ACEITA') {
            if ($isCandidato) {
                // mostrar dados da instituição ao candidato
                $instUser = optional($proposta->vaga)->instituicao->user ?? null;
                $data['contatos'] = [
                    'email'    => $instUser->email ?? null,
                    'telefone' => optional($proposta->vaga->instituicao)->celular_corporativo
                                  ?? optional($proposta->vaga->instituicao)->telefone_fixo
                                  ?? null,
                ];
            } else {
                // mostrar dados do candidato à instituição
                $candUser = optional($proposta->candidato)->user ?? null;
                $data['contatos'] = [
                    'email'    => $candUser->email ?? null,
                    'telefone' => optional($proposta->candidato)->telefone ?? null,
                    'cpf'      => optional($proposta->candidato)->cpf ?? null,
                ];
            }
        } else {
            // mascarar dados sensíveis quando não aceita
            unset($data['candidato']['user']['email']);
            unset($data['candidato']['user']['telefone']);
            unset($data['candidato']['cpf']);
            if (isset($data['vaga']['instituicao'])) {
                unset($data['vaga']['instituicao']['email_corporativo']);
                unset($data['vaga']['instituicao']['telefone_fixo']);
                unset($data['vaga']['instituicao']['celular_corporativo']);
            }
        }

        return response()->json($data);
    }

    /**
     * POST /propostas
     * body: { id_vaga, id_candidato, mensagem }
     * Valida ownership: candidato só cria para si; instituição só para vagas próprias.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_vaga'      => 'required|exists:vagas,id_vaga',
            'id_candidato' => 'required|exists:candidatos,id_candidato',
            'mensagem'     => 'required|string|max:2000',
        ]);

        $user = $request->user();
        $iniciador = $user->tipo_usuario ?? '';

        if ($iniciador === 'CANDIDATO') {
            $cid = optional($user->candidato)->id;
            if ($cid !== (int)$data['id_candidato']) {
                throw ValidationException::withMessages(['id_candidato' => 'Candidato inválido.']);
            }
        } elseif ($iniciador === 'INSTITUICAO') {
            $vaga = Vaga::where('id_vaga', $data['id_vaga'])->firstOrFail();
            if ((int)$vaga->id_instituicao !== (int)optional($user->instituicao)->id) {
                throw ValidationException::withMessages(['id_vaga' => 'Vaga não pertence à instituição.']);
            }
        } else {
            return $this->forbidden();
        }

        $proposta = Proposta::create([
            'id_vaga'        => $data['id_vaga'],
            'id_candidato'   => $data['id_candidato'],
            'mensagem'       => $data['mensagem'],
            'iniciador'      => $iniciador,     // CANDIDATO | INSTITUICAO
            'status'         => 'ENVIADA',      // enum maiúsculo
            'data_envio'     => now(),
        ]);

        return response()->json([
            'message'  => 'Proposta enviada',
            'proposta' => $proposta->fresh(),
        ], 201);
    }

    /**
     * PUT /propostas/{id}/aceitar
     * Marca como ACEITA e retorna contatos da outra parte.
     */
    public function accept($id, Request $request)
    {
        $user = $request->user();

        $proposta = Proposta::with(['vaga.instituicao.user', 'candidato.user'])->findOrFail($id);

        // Só quem recebeu pode aceitar
        $recebidaPorCandidato = $proposta->iniciador === 'INSTITUICAO';
        $recebidaPorInstituicao = $proposta->iniciador === 'CANDIDATO';

        $canAccept =
            ($recebidaPorCandidato && ($user->tipo_usuario === 'CANDIDATO') && optional($user->candidato)->id === $proposta->id_candidato)
            ||
            ($recebidaPorInstituicao && ($user->tipo_usuario === 'INSTITUICAO') && optional($user->instituicao)->id === optional($proposta->vaga)->id_instituicao);

        if (!$canAccept) return $this->forbidden();

        $proposta->update([
            'status'            => 'ACEITA',
            'data_resposta'     => now(),
            'mensagem_resposta' => $request->input('mensagem_resposta'),
        ]);

        // contatos da outra parte (para UX imediata, embora o front busque em GET /propostas/{id})
        if ($user->tipo_usuario === 'CANDIDATO') {
            $instUser = optional($proposta->vaga)->instituicao->user ?? null;
            $contatos = [
                'email'    => $instUser->email ?? null,
                'telefone' => optional($proposta->vaga->instituicao)->celular_corporativo
                              ?? optional($proposta->vaga->instituicao)->telefone_fixo
                              ?? null,
            ];
        } else {
            $candUser = optional($proposta->candidato)->user ?? null;
            $contatos = [
                'email'    => $candUser->email ?? null,
                'telefone' => optional($proposta->candidato)->telefone ?? null,
                'cpf'      => optional($proposta->candidato)->cpf ?? null,
            ];
        }

        return response()->json([
            'message'  => 'Proposta aceita',
            'contatos' => $contatos,
        ]);
    }

    /**
     * PUT /propostas/{id}/recusar
     */
    public function reject($id, Request $request)
    {
        $user = $request->user();
        $proposta = Proposta::with('vaga')->findOrFail($id);

        // Só quem recebeu pode recusar
        $recebidaPorCandidato = $proposta->iniciador === 'INSTITUICAO';
        $recebidaPorInstituicao = $proposta->iniciador === 'CANDIDATO';

        $canReject =
            ($recebidaPorCandidato && ($user->tipo_usuario === 'CANDIDATO') && optional($user->candidato)->id === $proposta->id_candidato)
            ||
            ($recebidaPorInstituicao && ($user->tipo_usuario === 'INSTITUICAO') && optional($user->instituicao)->id === optional($proposta->vaga)->id_instituicao);

        if (!$canReject) return $this->forbidden();

        $proposta->update([
            'status'            => 'RECUSADA',
            'data_resposta'     => now(),
            'mensagem_resposta' => $request->input('mensagem_resposta'),
        ]);

        return response()->json(['message' => 'Proposta recusada']);
    }

    /**
     * DELETE /propostas/{id}
     * Somente iniciador pode cancelar e apenas se não finalizada.
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $proposta = Proposta::with('vaga')->findOrFail($id);

        $isInitiator =
            ($proposta->iniciador === 'CANDIDATO'   && ($user->tipo_usuario === 'CANDIDATO')   && optional($user->candidato)->id === $proposta->id_candidato)
            ||
            ($proposta->iniciador === 'INSTITUICAO' && ($user->tipo_usuario === 'INSTITUICAO') && optional($user->instituicao)->id === optional($proposta->vaga)->id_instituicao);

        if (!$isInitiator) return $this->forbidden();

        if (in_array($proposta->status, ['ACEITA', 'RECUSADA'], true)) {
            return $this->unprocessable('Proposta já finalizada.');
        }

        $proposta->delete();

        return response()->json(['message' => 'Proposta cancelada']);
    }
}
