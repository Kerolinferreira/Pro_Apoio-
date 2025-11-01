<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Vaga;

class VagaController extends Controller
{
    /**
     * GET /vagas?q=&cidade=&regime=&per_page=
     * Lista vagas ABERTAS com filtros.
     */
    public function index(Request $request)
    {
        $perPage = $this->safePerPage($request, 10);

        $query = Vaga::query()
            ->with('instituicao')
            ->where('status', 'ABERTA');

            if ($term = trim((string) $request->input('q'))) {
                // Limita o tamanho do termo para evitar abuso
                if (strlen($term) > 100) {
                    return $this->error('Termo muito longo.', 400);
                }
        if ($cidade = trim((string) $request->input('cidade'))) {
            $query->where('cidade', $cidade);
        }

        if ($regime = trim((string) $request->input('regime'))) {
            $query->where('regime_contratacao', strtoupper($regime));
        }

        $query->orderByDesc('id_vaga');

        return $query->paginate($perPage)->appends($request->query());
    }

    /**
     * GET /vagas/{id}
     * Detalhe público de vaga ABERTA.
     */
    public function showPublic($id)
    {
        $vaga = Vaga::with(['deficiencias', 'instituicao'])
            ->where('id_vaga', $id)
            ->where('status', 'ABERTA')
            ->firstOrFail();

        return response()->json($vaga);
    }

    /**
     * POST /vagas
     * Cria vaga (somente instituição).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
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
            'carga_horaria_semanal'   => 'required|integer|min:1|max:60',
            'regime_contratacao'      => 'required|string|max:30',
            'valor_remuneracao'       => 'nullable|numeric|min:0',
            'tipo_remuneracao'        => 'required|string|max:30',
            'titulo_vaga'             => 'required|string|max:255',
            'cidade'                  => 'nullable|string|max:120',
            'estado'                  => 'nullable|string|size:2',
        ]);

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }
        $data['id_instituicao']     = $user->instituicao->id;
        $data['status']             = 'ABERTA';
        $data['regime_contratacao'] = strtoupper($data['regime_contratacao']);
        $data['tipo_remuneracao']   = strtoupper($data['tipo_remuneracao']);

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
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        // Adicionar paginação
        $perPage = $this->safePerPage($request, 15);

        $query = Vaga::where('id_instituicao', $user->instituicao->id)
            ->withCount('propostas')
            ->orderByDesc('id_vaga');

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /instituicao/vagas/{id}
     * Detalhe de vaga da instituição.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)
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
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', $user->instituicao->id)
            ->firstOrFail();

        $anoAtual = (int)date('Y');
        $anoMinimo = $anoAtual - 100; // Máximo 100 anos atrás
        $anoMaximo = $anoAtual; // Até o ano atual

        $data = $request->validate([
            'status'                 => 'nullable|string|in:ABERTA,PAUSADA,FECHADA',
            'aluno_nascimento_mes'   => 'nullable|integer|min:1|max:12',
            'aluno_nascimento_ano'   => "nullable|integer|min:{$anoMinimo}|max:{$anoMaximo}",
            'deficiencia_ids'        => 'nullable|array',
            'deficiencia_ids.*'      => 'integer|exists:deficiencias,id_deficiencia',
            'necessidades_descricao' => 'nullable|string|max:2000',
            'carga_horaria_semanal'  => 'nullable|integer|min:1|max:60',
            'regime_contratacao'     => 'nullable|string|max:30',
            'valor_remuneracao'      => 'nullable|numeric|min:0',
            'tipo_remuneracao'       => 'nullable|string|max:30',
            'titulo_vaga'            => 'nullable|string|max:255',
            'cidade'                 => 'nullable|string|max:120',
            'estado'                 => 'nullable|string|size:2',
        ]);

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
     */
    public function pause(Request $request, $id)
    {
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', $user->instituicao->id)
            ->firstOrFail();

        $vaga->update(['status' => 'PAUSADA']);

        return response()->json($vaga);
    }

    /**
     * PUT /vagas/{id}/fechar
     */
    public function close(Request $request, $id)
    {
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return $this->forbidden();
        }

        if (!$user->instituicao) {
            return $this->forbidden('Instituição não encontrada.');
        }

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', $user->instituicao->id)
            ->firstOrFail();

        $vaga->update(['status' => 'FECHADA']);

        return response()->json($vaga);
    }
}
