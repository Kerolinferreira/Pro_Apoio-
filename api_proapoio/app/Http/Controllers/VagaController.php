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

        $q = Vaga::query()
            ->with('instituicao')
            ->where('status', 'ABERTA');

        if ($term = trim((string) $request->input('q'))) {
            $q->where('titulo_vaga', 'like', "%{$term}%");
        }

        if ($cidade = trim((string) $request->input('cidade'))) {
            $q->where('cidade', $cidade);
        }

        if ($regime = trim((string) $request->input('regime'))) {
            $q->where('regime_contratacao', strtoupper($regime));
        }

        $q->orderByDesc('id_vaga');

        return $q->paginate($perPage)->appends($request->query());
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

        $data = $request->validate([
            'aluno_nascimento_mes'    => 'nullable|integer|min:1|max:12',
            'aluno_nascimento_ano'    => 'nullable|integer|min:1900|max:2100',
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

        $data['id_instituicao']     = optional($user->instituicao)->id;
        $data['status']             = 'ABERTA';
        $data['regime_contratacao'] = strtoupper($data['regime_contratacao']);
        $data['tipo_remuneracao']   = strtoupper($data['tipo_remuneracao']);

        $vaga = Vaga::create(collect($data)->except('deficiencia_ids')->toArray());

        if (!empty($data['deficiencia_ids'])) {
            $vaga->deficiencias()->sync($data['deficiencia_ids']);
        }

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

        $vagas = Vaga::where('id_instituicao', optional($user->instituicao)->id ?? 0)
            ->withCount('propostas')
            ->orderByDesc('id_vaga')
            ->get();

        return response()->json($vagas);
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

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', optional($user->instituicao)->id ?? 0)
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

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', optional($user->instituicao)->id ?? 0)
            ->firstOrFail();

        $data = $request->validate([
            'status'                 => 'nullable|string|in:ABERTA,PAUSADA,FECHADA',
            'aluno_nascimento_mes'   => 'nullable|integer|min:1|max:12',
            'aluno_nascimento_ano'   => 'nullable|integer|min:1900|max:2100',
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

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', optional($user->instituicao)->id ?? 0)
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

        $vaga = Vaga::where('id_vaga', $id)
            ->where('id_instituicao', optional($user->instituicao)->id ?? 0)
            ->firstOrFail();

        $vaga->update(['status' => 'FECHADA']);

        return response()->json($vaga);
    }

    /**
     * POST /vagas/{id}/salvar
     * Salva vaga para o candidato logado.
     */
    public function salvar(Request $request, $id)
    {
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return $this->forbidden('Apenas candidatos podem salvar vagas.');
        }

        $vaga = Vaga::where('id_vaga', $id)->where('status', 'ABERTA')->firstOrFail();

        $cid = optional($user->candidato)->id ?? 0;

        // evita duplicado
        $exists = DB::table('vagas_salvas')
            ->where('id_candidato', $cid)
            ->where('id_vaga', $vaga->id_vaga)
            ->exists();

        if (!$exists) {
            DB::table('vagas_salvas')->insert([
                'id_candidato' => $cid,
                'id_vaga'      => $vaga->id_vaga,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return response()->json(['message' => 'Vaga salva.'], 201);
    }

    /**
     * DELETE /vagas/{id}/salvar
     * Remove vaga salva do candidato.
     */
    public function unsalvar(Request $request, $id)
    {
        $user = $request->user();
        if (($user->tipo_usuario ?? '') !== 'CANDIDATO') {
            return $this->forbidden('Apenas candidatos podem remover vagas salvas.');
        }

        $cid = optional($user->candidato)->id ?? 0;

        DB::table('vagas_salvas')
            ->where('id_candidato', $cid)
            ->where('id_vaga', $id)
            ->delete();

        return response()->json(['message' => 'Vaga removida dos salvos.']);
    }
}
