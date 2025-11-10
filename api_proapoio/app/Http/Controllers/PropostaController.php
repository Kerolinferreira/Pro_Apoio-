<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Proposta;
use App\Models\Vaga;
use App\Models\User;
use App\Enums\PropostaStatus;
use App\Notifications\NovaPropostaNotification;
use App\Notifications\PropostaAceitaNotification;
use App\Notifications\PropostaRecusadaNotification;

class PropostaController extends Controller
{
    /**
     * Verifica se o tipo do usuário é Candidato (case-insensitive)
     */
    private function isCandidato($tipoUsuario): bool
    {
        return strtoupper($tipoUsuario ?? '') === 'CANDIDATO';
    }

    /**
     * Verifica se o tipo do usuário é Instituicao (case-insensitive)
     */
    private function isInstituicao($tipoUsuario): bool
    {
        return strtoupper($tipoUsuario ?? '') === 'INSTITUICAO';
    }

    /**
     * Sanitiza HTML removendo scripts e outras tags perigosas.
     */
    private function sanitizeHtml(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Remove scripts completamente (tag + conteúdo)
        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);
        $text = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $text);

        // Remove todas as outras tags HTML
        return strip_tags($text);
    }

    /**
     * Carrega o usuário autenticado com seus relacionamentos
     */
    private function getAuthenticatedUser(Request $request)
    {
        $user = $request->user();
        $user->load('candidato', 'instituicao');
        return $user;
    }

    /**
     * Normaliza proposta para compatibilidade com frontend
     * Inclui mascaramento de dados sensíveis se proposta não está aceita
     */
    private function normalizeProposta($proposta)
    {
        $data = is_array($proposta) ? $proposta : $proposta->toArray();

        // Normalizar status para lowercase
        if (isset($data['status'])) {
            $data['status'] = strtolower($data['status']);
        }

        // Mascarar dados sensíveis se proposta não está ACEITA
        if ($data['status'] !== 'aceita') {
            $data = $this->maskSensitiveData($data);
        }

        return $data;
    }

    /**
     * Mascara dados sensíveis (email, telefone, CPF) em propostas não aceitas
     */
    private function maskSensitiveData($data)
    {
        // Mascarar dados do candidato
        if (isset($data['candidato'])) {
            if (isset($data['candidato']['cpf'])) {
                $data['candidato']['cpf'] = $this->maskCpf($data['candidato']['cpf']);
            }
            if (isset($data['candidato']['telefone'])) {
                $data['candidato']['telefone'] = $this->maskPhone($data['candidato']['telefone']);
            }
            if (isset($data['candidato']['user']['email'])) {
                $data['candidato']['user']['email'] = $this->maskEmail($data['candidato']['user']['email']);
            }
        }

        // Mascarar dados da instituição
        if (isset($data['vaga']['instituicao'])) {
            if (isset($data['vaga']['instituicao']['email_corporativo'])) {
                $data['vaga']['instituicao']['email_corporativo'] = $this->maskEmail($data['vaga']['instituicao']['email_corporativo']);
            }
            if (isset($data['vaga']['instituicao']['telefone_fixo'])) {
                $data['vaga']['instituicao']['telefone_fixo'] = $this->maskPhone($data['vaga']['instituicao']['telefone_fixo']);
            }
            if (isset($data['vaga']['instituicao']['celular_corporativo'])) {
                $data['vaga']['instituicao']['celular_corporativo'] = $this->maskPhone($data['vaga']['instituicao']['celular_corporativo']);
            }
        }

        return $data;
    }

    /**
     * Mascara email preservando primeiro e último caractere antes do @
     * Exemplo: j***e@example.com
     */
    private function maskEmail($email)
    {
        if (empty($email) || !str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        if (strlen($local) <= 2) {
            return substr($local, 0, 1) . '***@' . $domain;
        }

        return substr($local, 0, 1) . str_repeat('*', strlen($local) - 2) . substr($local, -1) . '@' . $domain;
    }

    /**
     * Mascara telefone mostrando apenas primeiros 4 e últimos 2 dígitos
     * Exemplo: (11) 98765-4321 → (11) 9***-**21
     */
    private function maskPhone($phone)
    {
        if (empty($phone)) {
            return '***';
        }

        // Remove tudo exceto dígitos
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) < 6) {
            return '***';
        }

        // Pega DDD (primeiros 2 dígitos)
        $ddd = substr($digits, 0, 2);
        // Pega últimos 2 dígitos
        $last = substr($digits, -2);

        return "($ddd) ****-**$last";
    }

    /**
     * Mascara CPF mostrando apenas primeiros 3 e últimos 2 dígitos
     * Exemplo: 123.456.789-10 → 123.***.***-10
     */
    private function maskCpf($cpf)
    {
        if (empty($cpf)) {
            return '***';
        }

        // Remove tudo exceto dígitos
        $digits = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($digits) !== 11) {
            return '***.***.***-**';
        }

        return substr($digits, 0, 3) . '.***.***-' . substr($digits, -2);
    }

    /**
     * Formata a resposta de paginação com estrutura {data, meta}
     */
    private function paginatedResponse($paginator)
    {
        // Normalizar todas as propostas
        $items = collect($paginator->items())->map(function ($proposta) {
            return $this->normalizeProposta($proposta);
        })->toArray();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /propostas?tipo=enviadas|recebidas&per_page=10
     * Candidato: enviadas = iniciador CANDIDATO; recebidas = INSTITUICAO.
     * Instituição: filtra por vagas da instituição.
     */
    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        $tipo = $request->input('tipo', 'enviadas'); // padrão

        $perPage = $this->safePerPage($request, 10);

        if ($this->isCandidato($user->tipo_usuario)) {
            if (!$user->candidato) {
                return $this->forbidden('Candidato não encontrado.');
            }
            $candidatoId = $user->candidato->id;

            $query = Proposta::with(['vaga.instituicao', 'candidato'])
                ->where('id_candidato', $candidatoId)
                ->when($tipo === 'recebidas',
                    fn($subQuery) => $subQuery->where('iniciador', 'INSTITUICAO'),
                    fn($subQuery) => $subQuery->where('iniciador', 'CANDIDATO')
                )
                ->latest();

            return $this->paginatedResponse($query->paginate($perPage));
        }

        // INSTITUICAO
        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }
        $instituicaoId = $user->instituicao->id;

        // Evita N+1: usa whereHas em vez de pluck + whereIn
        $query = Proposta::with(['vaga.instituicao', 'candidato'])
            ->whereHas('vaga', fn($q) => $q->where('id_instituicao', $instituicaoId))
            ->when($tipo === 'recebidas',
                fn($subQuery) => $subQuery->where('iniciador', 'CANDIDATO'),
                fn($subQuery) => $subQuery->where('iniciador', 'INSTITUICAO')
            )
            ->latest();

        return $this->paginatedResponse($query->paginate($perPage));
    }

    /**
     * GET /propostas/{id}
     * Se status = ACEITA, inclui contatos da outra parte em "contatos".
     */
    public function show($id, Request $request)
    {
        $user = $this->getAuthenticatedUser($request);

        $proposta = Proposta::with([
            'vaga.instituicao.user',
            'candidato.user',
        ])->findOrFail($id);

        // autorização básica: precisa ser candidato envolvido OU instituição dona da vaga
        $isCandidato = $this->isCandidato($user->tipo_usuario)
            && $user->candidato
            && $user->candidato->id === $proposta->id_candidato;

        $isInstituicao = $this->isInstituicao($user->tipo_usuario)
            && $user->instituicao
            && $proposta->vaga
            && $user->instituicao->id === $proposta->vaga->id_instituicao;

        if (!$isCandidato && !$isInstituicao) {
            return $this->forbidden();
        }

        $data = $proposta->toArray();

        // Normalizar status para lowercase para compatibilidade com frontend
        $data['status'] = strtolower($data['status']);

        // contatos apenas quando ACEITA
        if ($proposta->status === PropostaStatus::ACEITA) {
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
            // mascarar dados sensíveis quando não aceita usando método centralizado
            $data = $this->maskSensitiveData($data);
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
        ], [
            'id_vaga.required' => 'Por favor, selecione uma vaga.',
            'id_vaga.exists' => 'A vaga selecionada não existe.',
            'id_candidato.required' => 'Por favor, selecione um candidato.',
            'id_candidato.exists' => 'O candidato selecionado não existe.',
            'mensagem.required' => 'Por favor, escreva uma mensagem.',
            'mensagem.max' => 'A mensagem não pode ter mais de 2000 caracteres.',
        ]);

        $user = $this->getAuthenticatedUser($request);
        $iniciador = strtoupper($user->tipo_usuario ?? '');

        if ($this->isCandidato($user->tipo_usuario)) {
            if (!$user->candidato) {
                return $this->forbidden('Candidato não encontrado.');
            }
            if ($user->candidato->id !== (int)$data['id_candidato']) {
                throw ValidationException::withMessages(['id_candidato' => 'Candidato inválido.']);
            }
        } elseif ($this->isInstituicao($user->tipo_usuario)) {
            if (!$user->instituicao) {
                return $this->forbidden('Instituição não encontrada.');
            }
            $vaga = Vaga::where('id_vaga', $data['id_vaga'])->firstOrFail();
            if ((int)$vaga->id_instituicao !== (int)$user->instituicao->id) {
                throw ValidationException::withMessages(['id_vaga' => 'Vaga não pertence à instituição.']);
            }
        } else {
            return $this->forbidden();
        }

        // CORREÇÃO: Validar se o candidato já possui uma proposta para esta vaga
        // Isso previne duplicidade de propostas (Falha Crítica #6)
        $jaSeCandidata = Proposta::where('id_vaga', $data['id_vaga'])
            ->where('id_candidato', $data['id_candidato'])
            ->exists();

        if ($jaSeCandidata) {
            throw ValidationException::withMessages([
                'id_vaga' => 'Você já possui uma proposta para esta vaga. Aguarde a resposta da instituição.'
            ]);
        }

        // CORREÇÃO: Validar se a vaga está ATIVA antes de permitir proposta (Falha #7)
        $vaga = Vaga::find($data['id_vaga']);
        if ($vaga && $vaga->status !== 'ATIVA') {
            throw ValidationException::withMessages([
                'id_vaga' => 'Esta vaga não está mais disponível para candidaturas.'
            ]);
        }

        $proposta = Proposta::create([
            'id_vaga'        => $data['id_vaga'],
            'id_candidato'   => $data['id_candidato'],
            'mensagem'       => $this->sanitizeHtml($data['mensagem']),
            'iniciador'      => $iniciador,     // CANDIDATO | INSTITUICAO
            'status'         => PropostaStatus::ENVIADA,
            'data_envio'     => now(),
        ]);

        // Disparar notificação para o receptor da proposta
        $proposta->load(['vaga.instituicao.user', 'candidato.user']);

        if ($this->isCandidato($iniciador)) {
            // Candidato enviou proposta → notificar instituição
            $receptorUser = $proposta->vaga?->instituicao?->user;
            if ($receptorUser) {
                $receptorUser->notify(new NovaPropostaNotification($proposta));
            }
        } else {
            // Instituição enviou proposta → notificar candidato
            $receptorUser = $proposta->candidato?->user;
            if ($receptorUser) {
                $receptorUser->notify(new NovaPropostaNotification($proposta));
            }
        }

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
        $user = $this->getAuthenticatedUser($request);

        $proposta = Proposta::with(['vaga.instituicao.user', 'candidato.user'])->findOrFail($id);

        // Só quem recebeu pode aceitar
        $recebidaPorCandidato = $this->isInstituicao($proposta->iniciador);
        $recebidaPorInstituicao = $this->isCandidato($proposta->iniciador);

        $canAccept =
            ($recebidaPorCandidato && $this->isCandidato($user->tipo_usuario) && $user->candidato && $user->candidato->id === $proposta->id_candidato)
            ||
            ($recebidaPorInstituicao && $this->isInstituicao($user->tipo_usuario) && $user->instituicao && $proposta->vaga && $user->instituicao->id === $proposta->vaga->id_instituicao);

        if (!$canAccept) return $this->forbidden();

        // Envolver em transação para garantir consistência
        $contatos = \Illuminate\Support\Facades\DB::transaction(function() use ($proposta, $request, $user) {
            $proposta->update([
                'status'            => PropostaStatus::ACEITA,
                'data_resposta'     => now(),
                'mensagem_resposta' => $this->sanitizeHtml($request->input('mensagem_resposta', '')),
            ]);

            // Notificar o iniciador da proposta sobre a aceitação
            if ($this->isCandidato($proposta->iniciador)) {
                // Candidato iniciou → notificar candidato
                $iniciadorUser = $proposta->candidato?->user;
            } else {
                // Instituição iniciou → notificar instituição
                $iniciadorUser = $proposta->vaga?->instituicao?->user;
            }

            if ($iniciadorUser) {
                $iniciadorUser->notify(new PropostaAceitaNotification($proposta));
            }

            // contatos da outra parte (para UX imediata, embora o front busque em GET /propostas/{id})
            if ($this->isCandidato($user->tipo_usuario)) {
                $instUser = optional($proposta->vaga)->instituicao->user ?? null;
                return [
                    'email'    => $instUser->email ?? null,
                    'telefone' => optional($proposta->vaga->instituicao)->celular_corporativo
                                  ?? optional($proposta->vaga->instituicao)->telefone_fixo
                                  ?? null,
                ];
            } else {
                $candUser = optional($proposta->candidato)->user ?? null;
                return [
                    'email'    => $candUser->email ?? null,
                    'telefone' => optional($proposta->candidato)->telefone ?? null,
                    'cpf'      => optional($proposta->candidato)->cpf ?? null,
                ];
            }
        });

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
        $user = $this->getAuthenticatedUser($request);
        $proposta = Proposta::with('vaga')->findOrFail($id);

        // Só quem recebeu pode recusar
        $recebidaPorCandidato = $this->isInstituicao($proposta->iniciador);
        $recebidaPorInstituicao = $this->isCandidato($proposta->iniciador);

        $canReject =
            ($recebidaPorCandidato && $this->isCandidato($user->tipo_usuario) && $user->candidato && $user->candidato->id === $proposta->id_candidato)
            ||
            ($recebidaPorInstituicao && $this->isInstituicao($user->tipo_usuario) && $user->instituicao && $proposta->vaga && $user->instituicao->id === $proposta->vaga->id_instituicao);

        if (!$canReject) return $this->forbidden();

        $proposta->update([
            'status'            => PropostaStatus::RECUSADA,
            'data_resposta'     => now(),
            'mensagem_resposta' => $this->sanitizeHtml($request->input('mensagem_resposta', '')),
        ]);

        // Notificar o iniciador da proposta sobre a recusa
        if ($this->isCandidato($proposta->iniciador)) {
            // Candidato iniciou → notificar candidato
            $iniciadorUser = $proposta->candidato?->user;
        } else {
            // Instituição iniciou → notificar instituição
            $iniciadorUser = $proposta->vaga?->instituicao?->user;
        }

        if ($iniciadorUser) {
            $iniciadorUser->notify(new PropostaRecusadaNotification($proposta));
        }

        return response()->json(['message' => 'Proposta recusada']);
    }

    /**
     * DELETE /propostas/{id}
     * Somente iniciador pode cancelar e apenas se não finalizada.
     */
    public function destroy($id, Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        $proposta = Proposta::with('vaga')->findOrFail($id);

        $isInitiator =
            ($this->isCandidato($proposta->iniciador) && $this->isCandidato($user->tipo_usuario) && $user->candidato && $user->candidato->id === $proposta->id_candidato)
            ||
            ($this->isInstituicao($proposta->iniciador) && $this->isInstituicao($user->tipo_usuario) && $user->instituicao && $proposta->vaga && $user->instituicao->id === $proposta->vaga->id_instituicao);

        if (!$isInitiator) return $this->forbidden();

        if (in_array($proposta->status, [PropostaStatus::ACEITA, PropostaStatus::RECUSADA], true)) {
            return $this->unprocessable('Proposta já finalizada.');
        }

        $proposta->delete();

        return response()->json(['message' => 'Proposta cancelada']);
    }
}
