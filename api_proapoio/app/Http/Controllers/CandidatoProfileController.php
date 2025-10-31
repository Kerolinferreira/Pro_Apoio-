<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Candidato;
use App\Models\ExperienciaProfissional;
use App\Models\ExperienciaPessoal;

class CandidatoProfileController extends Controller
{
    /** Retorna o perfil completo do candidato logado. */
    public function show(Request $request)
    {
        $user = $request->user();

        $candidato = Candidato::where('id_usuario', $user->id)
            ->with([
                'endereco',
                'experienciasProfissionais.deficiencias',
                'experienciasPessoais',
            ])->firstOrFail();

        return response()->json($candidato);
    }

    /** Atualiza dados do candidato e do endereço (cria endereço se ausente). */
    public function update(Request $request)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->with('endereco')->firstOrFail();

        $data = $request->validate([
            // Endereço
            'cep'              => 'nullable|string',
            'logradouro'       => 'nullable|string',
            'bairro'           => 'nullable|string',
            'cidade'           => 'nullable|string',
            'estado'           => 'nullable|string|max:2',
            'numero'           => 'nullable|string',
            'complemento'      => 'nullable|string',
            'ponto_referencia' => 'nullable|string',
            // Candidato
            'telefone'            => 'nullable|string',
            'cpf'                 => 'nullable|string',
            'link_perfil'         => 'nullable|url',
            'nivel_escolaridade'  => 'nullable|string',
            'curso_superior'      => 'nullable|string',
            'instituicao_ensino'  => 'nullable|string',
            'status'              => 'nullable|string',
        ]);

        // normalizações
        if (isset($data['telefone'])) $data['telefone'] = preg_replace('/\D+/', '', $data['telefone']);
        if (isset($data['cpf']))      $data['cpf']      = preg_replace('/\D+/', '', $data['cpf']);
        if (isset($data['cep']))      $data['cep']      = preg_replace('/\D+/', '', $data['cep']);

        // atualiza candidato
        $candidato->update(collect($data)->only([
            'telefone','cpf','link_perfil','nivel_escolaridade','curso_superior','instituicao_ensino','status',
        ])->toArray());

        // cria/atualiza endereço
        $enderecoPayload = collect($data)->only([
            'cep','logradouro','bairro','cidade','estado','numero','complemento','ponto_referencia',
        ])->filter();

        if ($enderecoPayload->isNotEmpty()) {
            if ($candidato->endereco) {
                $candidato->endereco->update($enderecoPayload->toArray());
            } else {
                $novo = $candidato->endereco()->create($enderecoPayload->toArray());
                $candidato->id_endereco = $novo->id;
                $candidato->save();
            }
        }

        $candidato->load('endereco');

        return response()->json($candidato);
    }

    /**
     * Upload da foto do candidato (até 2MB).
     * Salva o caminho em foto_url com validação robusta de segurança.
     */
    public function uploadFoto(Request $request)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        // Validação robusta de imagem
        $request->validate([
            'foto' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
            ],
        ]);

        $file = $request->file('foto');

        // Validação adicional de MIME type real (não apenas extensão)
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json(['message' => 'Tipo de arquivo inválido.'], 400);
        }

        // Remove foto antiga se existir
        if ($candidato->foto_url) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($candidato->foto_url);
        }

        // Gera nome seguro e único para o arquivo
        $extension = $file->getClientOriginalExtension();
        $filename = 'candidato_' . $candidato->id . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $extension;

        $path = $file->storeAs('fotos-candidatos', $filename, 'public');
        $candidato->update(['foto_url' => $path]);

        return response()->json(['foto_url' => $path]);
    }

    /**
     * Cria experiência profissional.
     * Compatível com:
     *  - objeto único no corpo
     *  - { experiencias: [ ... ] }
     * Aceita chaves do front:
     *  - idade_aluno, tempo_experiencia, candidatar_mesma_deficiencia, comentario, deficiencia_ids[]
     * E chaves antigas:
     *  - interesse_mesma_deficiencia, descricao
     */
    public function storeExperienciaPro(Request $request)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $experienciasPayload = $request->has('experiencias')
            ? $request->input('experiencias')
            : [$request->all()];

        if (!is_array($experienciasPayload)) {
            return response()->json(['message' => 'O campo experiencias deve ser um array de objetos.'], 422);
        }

        $criadas = [];

        foreach ($experienciasPayload as $expData) {
            // mapear chaves do front para o schema da tabela
            $mapped = [
                'idade_aluno'                 => $expData['idade_aluno'] ?? null,
                'tempo_experiencia'           => $expData['tempo_experiencia'] ?? ($expData['tempo'] ?? null),
                'interesse_mesma_deficiencia' => array_key_exists('candidatar_mesma_deficiencia', $expData)
                    ? (bool)$expData['candidatar_mesma_deficiencia']
                    : (bool)($expData['interesse_mesma_deficiencia'] ?? false),
                'descricao'                   => $expData['comentario'] ?? ($expData['descricao'] ?? null),
                'deficiencia_ids'             => $expData['deficiencia_ids'] ?? [],
            ];

            // validação por item
            $validated = validator($mapped, [
                'idade_aluno'                 => 'nullable|integer|min:0|max:120',
                'tempo_experiencia'           => 'nullable|string|max:255',
                'interesse_mesma_deficiencia' => 'nullable|boolean',
                'descricao'                   => 'nullable|string|max:1000',
                'deficiencia_ids'             => 'nullable|array',
                'deficiencia_ids.*'           => 'integer|exists:deficiencias,id_deficiencia',
            ])->validate();

            $validated['id_candidato'] = $candidato->id;

            $experiencia = ExperienciaProfissional::create(collect($validated)->except('deficiencia_ids')->toArray());

            if (!empty($validated['deficiencia_ids'])) {
                $experiencia->deficiencias()->sync($validated['deficiencia_ids']);
            }

            $criadas[] = $experiencia->load('deficiencias');
        }

        return response()->json(count($criadas) === 1 ? $criadas[0] : $criadas, 201);
    }

    /** Remove experiência profissional do candidato. */
    public function deleteExperienciaPro(Request $request, $id)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $exp = ExperienciaProfissional::where('id_experiencia_profissional', $id)
            ->where('id_candidato', $candidato->id)
            ->firstOrFail();

        $exp->delete();

        return response()->json(['message' => 'Experiência profissional removida.']);
    }

    /** Cria experiência pessoal. */
    public function storeExperienciaPessoal(Request $request)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $data = $request->validate([
            'interesse_atuar' => 'nullable|boolean',
            'descricao'       => 'nullable|string|max:1000',
        ]);

        $data['id_candidato'] = $candidato->id;

        $exp = ExperienciaPessoal::create($data);

        return response()->json($exp, 201);
    }

    /** Remove experiência pessoal. */
    public function deleteExperienciaPessoal(Request $request, $id)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $exp = ExperienciaPessoal::where('id_experiencia_pessoal', $id)
            ->where('id_candidato', $candidato->id)
            ->firstOrFail();

        $exp->delete();

        return response()->json(['message' => 'Experiência pessoal removida.']);
    }

    /** Troca de senha do usuário autenticado. */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            // mínimo 8, letras e números
            'password'         => ['required','string','confirmed','min:8','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        if (!Hash::check($data['current_password'], $user->senha_hash)) {
            return response()->json(['message' => 'Senha atual incorreta.'], 400);
        }

        $user->update(['senha_hash' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Senha atualizada com sucesso.']);
    }

    /** Exclui a conta do usuário autenticado. */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $request->validate(['password' => 'required|string']);

        if (!Hash::check($request->input('password'), $user->senha_hash)) {
            return response()->json(['message' => 'Senha inválida.'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Conta removida com sucesso.']);
    }
}
