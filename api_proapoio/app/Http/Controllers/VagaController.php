<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Vaga;

class VagaController extends Controller
{
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
     * Verifica se o tipo do usuário é Instituicao (case-insensitive)
     */
    private function isInstituicao($tipoUsuario): bool
    {
        return strtoupper($tipoUsuario ?? '') === 'INSTITUICAO';
    }

    /**
     * Formata a resposta de paginação com estrutura {data, meta}
     */
    private function paginatedResponse($paginator)
    {
        return response()->json([
            'data' => $paginator->items(),
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
     * GET /vagas?q=&cidade=&regime=&per_page=
     * Lista vagas ATIVAS com filtros.
     */
    public function index(Request $request)
    {
        $perPage = $this->safePerPage($request, 10);

        $query = Vaga::query()
            ->with('instituicao')
            ->where('status', 'ATIVA');

        // Busca por termo no título da vaga e descrição de necessidades
        if ($term = trim((string) $request->input('q'))) {
            // Limita o tamanho do termo para evitar abuso
            if (strlen($term) > 100) {
                return $this->error('Termo muito longo.', 400);
            }

            // Escapar caracteres especiais do LIKE para evitar interpretação como wildcards
            $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $query->where(function ($subQuery) use ($escapedTerm) {
                $subQuery->where('titulo_vaga', 'LIKE', "%{$escapedTerm}%")
                         ->orWhere('necessidades_descricao', 'LIKE', "%{$escapedTerm}%");
            });
        }

        // Filtro por cidade
        if ($cidade = trim((string) $request->input('cidade'))) {
            $query->where('cidade', $cidade);
        }

        // Filtro por estado (valida UF brasileira)
        if ($estado = strtoupper(trim((string) $request->input('estado')))) {
            $estadosValidos = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                              'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                              'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
            if (in_array($estado, $estadosValidos, true)) {
                $query->where('estado', $estado);
            }
        }

        // Filtro por tipo (valida contra valores permitidos)
        if ($tipo = $request->input('tipo')) {
            $tiposValidos = ['CLT', 'PJ', 'Estágio', 'Temporário', 'Voluntariado', 'Trabalho Fixo'];

            if (is_array($tipo)) {
                // Filtra apenas valores válidos
                $tiposFiltrados = array_intersect($tipo, $tiposValidos);
                if (!empty($tiposFiltrados)) {
                    $query->whereIn('tipo', $tiposFiltrados);
                }
            } else {
                // Valida valor único
                if (in_array($tipo, $tiposValidos, true)) {
                    $query->where('tipo', $tipo);
                }
            }
        }

        // Filtro por regime de contratação
        if ($regime = trim((string) $request->input('regime'))) {
            $query->where('regime_contratacao', strtoupper($regime));
        }

        // Ordenação por mais recentes
        $query->orderByDesc('id_vaga');

        return $this->paginatedResponse($query->paginate($perPage));
    }

    /**
     * GET /vagas/{id}
     * Detalhe público de vaga ATIVA.
     */
    public function showPublic($id)
    {
        $vaga = Vaga::with(['deficiencias', 'instituicao'])
            ->where('id_vaga', $id)
            ->where('status', 'ATIVA')
            ->firstOrFail();

        return response()->json($vaga);
    }

    /**
     * POST /vagas
     * Cria vaga (somente instituição).
     */
    public function store(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden('Apenas instituições podem criar vagas.');
        }

        $anoAtual = (int)date('Y');
        $anoMinimo = $anoAtual - 100; // Máximo 100 anos atrás
        $anoMaximo = $anoAtual; // Até o ano atual

        $data = $request->validate([
            'aluno_nascimento_mes'    => 'nullable|integer|min:1|max:12',
            'aluno_nascimento_ano'    => "nullable|integer|min:{$anoMinimo}|max:{$anoMaximo}",
            'deficiencia_ids'         => 'nullable|array',
            'deficiencia_ids.*'       => 'integer|exists:deficiencias,id_deficiencia',
            'necessidades_descricao'  => 'nullable|string|max:2000',
            'descricao'               => 'nullable|string|max:2000',
            'carga_horaria_semanal'   => 'nullable|integer|min:1|max:60',
            'regime_contratacao'      => 'nullable|string|max:30',
            'valor_remuneracao'       => 'nullable|numeric|min:0',
            'remuneracao'             => 'nullable|numeric|min:0',
            'tipo_remuneracao'        => 'nullable|string|max:30',
            'titulo_vaga'             => 'required|string|min:3|max:255',
            'titulo'                  => 'nullable|string|min:3|max:255',
            'tipo'                    => 'nullable|string',
            'modalidade'              => 'nullable|string',
            'cidade'                  => 'nullable|string|max:120',
            'estado'                  => 'nullable|string|size:2',
        ], [
            'aluno_nascimento_mes.min' => 'O mês de nascimento deve estar entre 1 e 12.',
            'aluno_nascimento_mes.max' => 'O mês de nascimento deve estar entre 1 e 12.',
            'aluno_nascimento_ano.min' => 'Ano de nascimento inválido.',
            'aluno_nascimento_ano.max' => 'Ano de nascimento inválido.',
            'deficiencia_ids.*.exists' => 'Uma ou mais deficiências selecionadas são inválidas.',
            'necessidades_descricao.max' => 'A descrição de necessidades não pode ter mais de 2000 caracteres.',
            'descricao.max' => 'A descrição não pode ter mais de 2000 caracteres.',
            'carga_horaria_semanal.min' => 'A carga horária semanal deve ser de pelo menos 1 hora.',
            'carga_horaria_semanal.max' => 'A carga horária semanal não pode ser maior que 60 horas.',
            'valor_remuneracao.min' => 'O valor da remuneração deve ser maior ou igual a zero.',
            'remuneracao.min' => 'O valor da remuneração deve ser maior ou igual a zero.',
            'titulo_vaga.required' => 'Por favor, informe o título da vaga.',
            'titulo_vaga.min' => 'O título da vaga deve ter pelo menos 3 caracteres.',
            'titulo_vaga.max' => 'O título da vaga não pode ter mais de 255 caracteres.',
            'titulo.min' => 'O título da vaga deve ter pelo menos 3 caracteres.',
            'titulo.max' => 'O título da vaga não pode ter mais de 255 caracteres.',
            'estado.size' => 'O estado deve ter exatamente 2 caracteres.',
        ]);

        // Mapear campos alternativos e garantir compatibilidade
        if (!isset($data['titulo_vaga']) && isset($data['titulo'])) {
            $data['titulo_vaga'] = $data['titulo'];
        }
        if (!isset($data['titulo']) && isset($data['titulo_vaga'])) {
            $data['titulo'] = $data['titulo_vaga'];
        }
        // Garantir que ambos os campos titulo estejam preenchidos
        if (isset($data['titulo_vaga'])) {
            $data['titulo'] = $data['titulo_vaga'];
        }
        if (!isset($data['valor_remuneracao']) && isset($data['remuneracao'])) {
            $data['valor_remuneracao'] = $data['remuneracao'];
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }
        $data['id_instituicao']     = $user->instituicao->id;
        $data['status']             = 'ATIVA';

        // Normalizar campos opcionais apenas se existirem
        if (isset($data['regime_contratacao'])) {
            $data['regime_contratacao'] = strtoupper($data['regime_contratacao']);
        }
        if (isset($data['tipo_remuneracao'])) {
            $data['tipo_remuneracao'] = strtoupper($data['tipo_remuneracao']);
        }

        // Envolver em transação para garantir consistência
        $vaga = DB::transaction(function() use ($data) {
            $vaga = Vaga::create(collect($data)->except('deficiencia_ids')->toArray());

            if (!empty($data['deficiencia_ids'])) {
                $vaga->deficiencias()->sync($data['deficiencia_ids']);
            }

            return $vaga;
        });

        return response()->json($vaga->load('deficiencias'), 201);
    }

    /**
     * GET /vagas/minhas
     * Vagas da instituição logada.
     */
    public function minhas(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vagas = Vaga::where('id_instituicao', $user->instituicao->id)
            ->withCount('propostas')
            ->orderByDesc('id_vaga')
            ->get();

        // Mapear para formato esperado pelo frontend
        $vagasMapeadas = $vagas->map(function ($vaga) {
            return [
                'id' => $vaga->id_vaga,
                'titulo' => $vaga->titulo_vaga,
                'descricao' => $vaga->descricao,
                'tipo' => $vaga->tipo,
                'modalidade' => $vaga->modalidade,
                'remuneracao' => $vaga->valor_remuneracao,
                'cidade' => $vaga->cidade,
                'estado' => $vaga->estado,
                'status' => $vaga->status,
                'data_criacao' => $vaga->created_at ? $vaga->created_at->toISOString() : null,
                'numero_propostas' => $vaga->propostas_count ?? 0,
            ];
        });

        return response()->json($vagasMapeadas);
    }

    /**
     * GET /instituicao/vagas/{id}
     * Detalhe de vaga da instituição.
     */
    public function show(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::with(['deficiencias'])
            ->where('id_vaga', $id)
            ->where('id_instituicao', $user->instituicao->id)
            ->firstOrFail();

        return response()->json($vaga);
    }

    /**
     * PUT /vagas/{id}
     * Atualiza vaga da instituição.
     */
    public function update(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)->first();

        if (!$vaga) {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        }

        if ($vaga->id_instituicao !== $user->instituicao->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $anoAtual = (int)date('Y');
        $anoMinimo = $anoAtual - 100; // Máximo 100 anos atrás
        $anoMaximo = $anoAtual; // Até o ano atual

        $data = $request->validate([
            'status'                 => 'nullable|string|in:ATIVA,PAUSADA,FECHADA',
            'aluno_nascimento_mes'   => 'nullable|integer|min:1|max:12',
            'aluno_nascimento_ano'   => "nullable|integer|min:{$anoMinimo}|max:{$anoMaximo}",
            'deficiencia_ids'        => 'nullable|array',
            'deficiencia_ids.*'      => 'integer|exists:deficiencias,id_deficiencia',
            'necessidades_descricao' => 'nullable|string|max:2000',
            'descricao'              => 'nullable|string|max:2000',
            'carga_horaria_semanal'  => 'nullable|integer|min:1|max:60',
            'regime_contratacao'     => 'nullable|string|max:30',
            'valor_remuneracao'      => 'nullable|numeric|min:0',
            'remuneracao'            => 'nullable|numeric|min:0',
            'tipo_remuneracao'       => 'nullable|string|max:30',
            'titulo_vaga'            => 'nullable|string|max:255',
            'titulo'                 => 'nullable|string|min:3|max:255',
            'tipo'                   => 'nullable|string|max:50',
            'modalidade'             => 'nullable|string|max:50',
            'cidade'                 => 'nullable|string|max:120',
            'estado'                 => 'nullable|string|size:2',
        ]);

        // Mapear campos alternativos
        if (!isset($data['titulo_vaga']) && isset($data['titulo'])) {
            $data['titulo_vaga'] = $data['titulo'];
        }
        if (!isset($data['valor_remuneracao']) && isset($data['remuneracao'])) {
            $data['valor_remuneracao'] = $data['remuneracao'];
        }

        if (isset($data['regime_contratacao'])) {
            $data['regime_contratacao'] = strtoupper($data['regime_contratacao']);
        }
        if (isset($data['tipo_remuneracao'])) {
            $data['tipo_remuneracao'] = strtoupper($data['tipo_remuneracao']);
        }

        $vaga->update(collect($data)->except('deficiencia_ids')->toArray());

        if (array_key_exists('deficiencia_ids', $data)) {
            $vaga->deficiencias()->sync($data['deficiencia_ids'] ?? []);
        }

        return response()->json($vaga->load('deficiencias'));
    }

    /**
     * PUT /vagas/{id}/pausar
     * Transição válida: ATIVA → PAUSADA
     */
    public function pause(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)->first();

        if (!$vaga) {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        }

        if ($vaga->id_instituicao !== $user->instituicao->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Validar transição de estado: apenas ATIVA pode ser pausada
        if ($vaga->status !== 'ATIVA') {
            return $this->unprocessable(
                'Apenas vagas ATIVAS podem ser pausadas. Status atual: ' . $vaga->status
            );
        }

        $vaga->update(['status' => 'PAUSADA']);

        return response()->json($vaga);
    }

    /**
     * PUT /vagas/{id}/fechar
     * Transição válida: ATIVA → FECHADA ou PAUSADA → FECHADA
     */
    public function close(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)->first();

        if (!$vaga) {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        }

        if ($vaga->id_instituicao !== $user->instituicao->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Validar transição de estado: ATIVA ou PAUSADA podem ser fechadas
        if (!in_array($vaga->status, ['ATIVA', 'PAUSADA'], true)) {
            return $this->unprocessable(
                'Apenas vagas ATIVAS ou PAUSADAS podem ser fechadas. Status atual: ' . $vaga->status
            );
        }

        $vaga->update(['status' => 'FECHADA']);

        return response()->json($vaga);
    }

    /**
     * PATCH /vagas/{id}/status
     * Altera o status de uma vaga com máquina de estados
     * Transições válidas:
     * - ATIVA → PAUSADA
     * - ATIVA → FECHADA
     * - PAUSADA → ATIVA
     * - PAUSADA → FECHADA
     * - FECHADA → (nenhuma transição permitida)
     */
    public function changeStatus(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $data = $request->validate([
            'status' => 'required|in:ATIVA,PAUSADA,FECHADA'
        ]);

        $vaga = Vaga::where('id_vaga', $id)->first();

        if (!$vaga) {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        }

        if ($vaga->id_instituicao !== $user->instituicao->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $statusAtual = $vaga->status;
        $novoStatus = $data['status'];

        // Se o status não mudou, retorna sucesso
        if ($statusAtual === $novoStatus) {
            return response()->json($vaga);
        }

        // Validar transições de estado usando máquina de estados
        $transicoesValidas = [
            'ATIVA' => ['PAUSADA', 'FECHADA'],
            'PAUSADA' => ['ATIVA', 'FECHADA'],
            'FECHADA' => [], // Nenhuma transição permitida
        ];

        if (!isset($transicoesValidas[$statusAtual]) ||
            !in_array($novoStatus, $transicoesValidas[$statusAtual], true)) {
            return $this->unprocessable(
                "Transição de status inválida: {$statusAtual} → {$novoStatus}. " .
                "Transições permitidas a partir de {$statusAtual}: " .
                (empty($transicoesValidas[$statusAtual])
                    ? 'nenhuma (status final)'
                    : implode(', ', $transicoesValidas[$statusAtual]))
            );
        }

        $vaga->update(['status' => $novoStatus]);

        return response()->json($vaga);
    }

    /**
     * DELETE /vagas/{id}
     * Remove uma vaga permanentemente e notifica candidatos com propostas
     */
    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$this->isInstituicao($user->tipo_usuario)) {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)->first();

        if (!$vaga) {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        }

        if ($vaga->id_instituicao !== $user->instituicao->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $vaga->load(['propostas.candidato.user', 'instituicao']);

        // Capturar dados da vaga antes de excluir (para notificações)
        $vagaData = [
            'id_vaga' => $vaga->id_vaga,
            'titulo_vaga' => $vaga->titulo_vaga ?? $vaga->titulo,
            'titulo' => $vaga->titulo,
            'nome_instituicao' => $vaga->instituicao->nome_fantasia ?? $vaga->instituicao->razao_social,
        ];

        // Envolver em transação para garantir consistência
        DB::transaction(function() use ($vaga, $vagaData) {
            // Notificar candidatos que possuem propostas para esta vaga
            if ($vaga->propostas->isNotEmpty()) {
                foreach ($vaga->propostas as $proposta) {
                    if ($proposta->candidato && $proposta->candidato->user) {
                        $proposta->candidato->user->notify(
                            new \App\Notifications\VagaExcluidaNotification($vagaData)
                        );
                    }
                }
            }

            // Soft delete da vaga (preserva registro com deleted_at preenchido)
            $vaga->delete();
        });

        return response()->json([
            'message' => 'Vaga removida com sucesso.',
            'candidatos_notificados' => $vaga->propostas->count(),
        ]);
    }
}
