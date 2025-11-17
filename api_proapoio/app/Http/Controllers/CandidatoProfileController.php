<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Candidato;
use App\Models\ExperienciaProfissional;
use App\Models\ExperienciaPessoal;

class CandidatoProfileController extends Controller
{
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

    /** Retorna o perfil completo do candidato logado. */
    public function show(Request $request)
    {
        $user = $request->user();

        $candidato = Candidato::where('id_usuario', $user->id)
            ->with([
                'endereco',
                'experienciasProfissionais.deficiencias',
                'experienciasPessoais.deficiencias', // CORREÇÃO P6: Incluir deficiências de experiências pessoais
            ])->firstOrFail();

        // CORREÇÃO P2: Adicionar email do usuário para compatibilidade com frontend
        $data = $candidato->toArray();
        $data['email'] = $user->email;

        // CORREÇÃO P2: Garantir que data_nascimento está no formato correto (YYYY-MM-DD)
        if (isset($data['data_nascimento']) && $data['data_nascimento']) {
            // Se for um objeto Carbon, converte para string Y-m-d
            if ($candidato->data_nascimento instanceof \Carbon\Carbon) {
                $data['data_nascimento'] = $candidato->data_nascimento->format('Y-m-d');
            }
        }

        return response()->json($data);
    }

    /** Atualiza dados do candidato e do endereço (cria endereço se ausente). */
    public function update(Request $request)
    {
        // Normaliza dados antes da validação
        if ($request->has('telefone')) {
            $request->merge(['telefone' => preg_replace('/\D+/', '', $request->input('telefone'))]);
        }
        if ($request->has('cpf')) {
            $request->merge(['cpf' => preg_replace('/\D+/', '', $request->input('cpf'))]);
        }
        if ($request->has('cep')) {
            $request->merge(['cep' => preg_replace('/\D+/', '', $request->input('cep'))]);
        }

        $data = $request->validate([
            // Endereço
            'cep'              => 'nullable|string|min:8|max:8',
            'logradouro'       => 'nullable|string|max:255',
            'bairro'           => 'nullable|string|max:100',
            'cidade'           => 'nullable|string|max:100',
            'estado'           => 'nullable|string|size:2',
            'numero'           => 'nullable|string|max:10',
            'complemento'      => 'nullable|string|max:100',
            'ponto_referencia' => 'nullable|string|max:255',
            // Candidato - Dados básicos
            'nome_completo'       => 'nullable|string|max:255',
            'data_nascimento'     => 'nullable|date',
            'genero'              => 'nullable|string|max:50',
            'telefone'            => 'nullable|string|min:10|max:11',
            'cpf'                 => 'nullable|string|min:11|max:11',
            'link_perfil'         => 'nullable|url|max:255',
            'nivel_escolaridade'  => 'nullable|string|max:100',
            'curso_superior'      => 'nullable|string|max:255',
            'instituicao_ensino'  => 'nullable|string|max:255',
            'experiencia'         => 'nullable|string|max:2000',
            'status'              => 'nullable|string|max:50',
            'deficiencias_atuadas'=> 'nullable|array',
            'deficiencias_atuadas.*' => 'integer|exists:deficiencias,id_deficiencia',
            // Senha atual para validar alteração de dados sensíveis
            'current_password'    => 'required_with:cpf,telefone|string',
        ], [
            // Mensagens de validação do endereço
            'cep.min' => 'O CEP deve ter 8 dígitos.',
            'cep.max' => 'O CEP deve ter 8 dígitos.',
            'logradouro.max' => 'O logradouro não pode ter mais de 255 caracteres.',
            'bairro.max' => 'O bairro não pode ter mais de 100 caracteres.',
            'cidade.max' => 'A cidade não pode ter mais de 100 caracteres.',
            'estado.size' => 'O estado deve ter exatamente 2 caracteres (ex: SP, RJ).',
            'numero.max' => 'O número não pode ter mais de 10 caracteres.',
            'complemento.max' => 'O complemento não pode ter mais de 100 caracteres.',
            'ponto_referencia.max' => 'O ponto de referência não pode ter mais de 255 caracteres.',

            // Mensagens de validação do candidato
            'nome_completo.max' => 'O nome completo não pode ter mais de 255 caracteres.',
            'data_nascimento.date' => 'A data de nascimento deve ser uma data válida.',
            'genero.max' => 'O gênero não pode ter mais de 50 caracteres.',
            'telefone.min' => 'O telefone deve ter no mínimo 10 dígitos.',
            'telefone.max' => 'O telefone deve ter no máximo 11 dígitos.',
            'cpf.min' => 'O CPF deve ter 11 dígitos.',
            'cpf.max' => 'O CPF deve ter 11 dígitos.',
            'link_perfil.url' => 'O link do perfil deve ser uma URL válida (ex: https://linkedin.com/in/seu-perfil).',
            'link_perfil.max' => 'O link do perfil não pode ter mais de 255 caracteres.',
            'nivel_escolaridade.max' => 'O nível de escolaridade não pode ter mais de 100 caracteres.',
            'curso_superior.max' => 'O nome do curso não pode ter mais de 255 caracteres.',
            'instituicao_ensino.max' => 'O nome da instituição de ensino não pode ter mais de 255 caracteres.',
            'status.max' => 'O status não pode ter mais de 50 caracteres.',
            'deficiencias_atuadas.array' => 'As deficiências atuadas devem ser um array.',
            'deficiencias_atuadas.*.integer' => 'Cada deficiência deve ser um número inteiro.',
            'deficiencias_atuadas.*.exists' => 'Uma ou mais deficiências selecionadas não existem.',

            // Mensagem de validação de senha
            'current_password.required_with' => 'Para alterar o CPF ou telefone, você precisa informar sua senha atual.',
        ]);

        try {
            $user = $request->user();
            $candidato = Candidato::where('id_usuario', $user->id)->with('endereco')->firstOrFail();

        // Validar senha atual se estiver alterando CPF ou telefone
        if (isset($data['current_password'])) {
            if (!Hash::check($data['current_password'], $user->senha_hash)) {
                return response()->json(['message' => 'Senha incorreta'], 400);
            }
        }

        // CORREÇÃO: Validar unicidade de CPF se estiver sendo alterado (Falha #4)
        if (isset($data['cpf']) && $data['cpf'] !== $candidato->cpf) {
            $cpfExistente = \App\Models\Candidato::where('cpf', $data['cpf'])
                ->where('id_candidato', '!=', $candidato->id_candidato)
                ->exists();

            if ($cpfExistente) {
                return response()->json([
                    'message' => 'CPF já cadastrado por outro usuário.',
                    'errors' => ['cpf' => ['Este CPF já está em uso.']]
                ], 422);
            }
        }

        // atualiza candidato
        $candidato->update(collect($data)->only([
            'nome_completo','data_nascimento','genero','telefone','cpf','link_perfil',
            'nivel_escolaridade','curso_superior','instituicao_ensino','experiencia','status',
        ])->toArray());

        // Atualiza deficiências atuadas (relacionamento many-to-many)
        if (isset($data['deficiencias_atuadas'])) {
            $candidato->deficiencias()->sync($data['deficiencias_atuadas']);
        }

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Candidato não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao atualizar perfil de candidato: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
            ], 500);
        }
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
        ], [
            'foto.required' => 'Por favor, selecione uma foto.',
            'foto.file' => 'O arquivo enviado não é válido.',
            'foto.image' => 'O arquivo deve ser uma imagem.',
            'foto.mimes' => 'A foto deve ser nos formatos: JPEG, JPG, PNG ou WEBP.',
            'foto.max' => 'A foto não pode ser maior que 2MB.',
            'foto.dimensions' => 'A foto deve ter no mínimo 100x100 pixels e no máximo 4000x4000 pixels.',
        ]);

        $file = $request->file('foto');

        // Validação adicional de MIME type real (não apenas extensão)
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json(['message' => 'Tipo de arquivo inválido.'], 400);
        }

        // Remove foto antiga se existir
        if ($candidato->foto_url) {
            // CORREÇÃO P21: Remove /storage/ prefix se presente
            $oldPath = str_replace('/storage/', '', $candidato->foto_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }

        // Gera nome seguro e único para o arquivo
        $extension = $file->getClientOriginalExtension();
        $filename = 'candidato_' . $candidato->id_candidato . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $extension;

        $path = $file->storeAs('fotos-candidatos', $filename, 'public');

        // CORREÇÃO P21: Retornar URL completa acessível pelo frontend
        $url = '/storage/' . $path;
        $candidato->update(['foto_url' => $url]);

        return response()->json(['foto_url' => $url]);
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

        // Envolver em transação para garantir consistência
        $criadas = \Illuminate\Support\Facades\DB::transaction(function() use ($experienciasPayload, $candidato) {
            $criadas = [];

            foreach ($experienciasPayload as $expData) {
                // mapear chaves do front para o schema da tabela
                $mapped = [
                    'idade_aluno'                 => $expData['idade_aluno'] ?? null,
                    'tempo_experiencia'           => $expData['tempo_experiencia'] ?? ($expData['tempo'] ?? null),
                    'interesse_mesma_deficiencia' => array_key_exists('candidatar_mesma_deficiencia', $expData)
                        ? (bool)$expData['candidatar_mesma_deficiencia']
                        : (bool)($expData['interesse_mesma_deficiencia'] ?? false),
                    'descricao'                   => $this->sanitizeHtml($expData['comentario'] ?? ($expData['descricao'] ?? '')),
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

            return $criadas;
        });

        return response()->json(count($criadas) === 1 ? $criadas[0] : $criadas, 201);
    }

    /**
     * Atualiza experiência profissional do candidato.
     * PUT /candidatos/me/experiencias-profissionais/{id}
     */
    public function updateExperienciaPro(Request $request, $id)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $experiencia = ExperienciaProfissional::where('id_experiencia_profissional', $id)
            ->where('id_candidato', $candidato->id)
            ->firstOrFail();

        // Mapear chaves do front para o schema da tabela
        $mapped = [
            'idade_aluno'                 => $request->input('idade_aluno'),
            'tempo_experiencia'           => $request->input('tempo_experiencia') ?? $request->input('tempo'),
            'interesse_mesma_deficiencia' => $request->has('candidatar_mesma_deficiencia')
                ? (bool)$request->input('candidatar_mesma_deficiencia')
                : (bool)$request->input('interesse_mesma_deficiencia', false),
            'descricao'                   => $this->sanitizeHtml($request->input('comentario') ?? $request->input('descricao', '')),
            'deficiencia_ids'             => $request->input('deficiencia_ids', []),
        ];

        // Validação
        $validated = validator($mapped, [
            'idade_aluno'                 => 'nullable|integer|min:0|max:120',
            'tempo_experiencia'           => 'nullable|string|max:255',
            'interesse_mesma_deficiencia' => 'nullable|boolean',
            'descricao'                   => 'nullable|string|max:1000',
            'deficiencia_ids'             => 'nullable|array',
            'deficiencia_ids.*'           => 'integer|exists:deficiencias,id_deficiencia',
        ])->validate();

        // Envolver em transação para garantir consistência
        $experiencia = \Illuminate\Support\Facades\DB::transaction(function() use ($experiencia, $validated) {
            $experiencia->update(collect($validated)->except('deficiencia_ids')->toArray());

            if (array_key_exists('deficiencia_ids', $validated)) {
                $experiencia->deficiencias()->sync($validated['deficiencia_ids'] ?? []);
            }

            return $experiencia->load('deficiencias');
        });

        return response()->json($experiencia);
    }

    /** Remove experiência profissional do candidato. */
    public function deleteExperienciaPro(Request $request, $id)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $experiencia = ExperienciaProfissional::where('id_experiencia_profissional', $id)
            ->where('id_candidato', $candidato->id)
            ->firstOrFail();

        $experiencia->delete();

        return response()->json(['message' => 'Experiência profissional removida.']);
    }

    /**
     * Cria experiência pessoal.
     * Compatível com:
     *  - objeto único no corpo
     *  - { experiencias: [ ... ] }
     * Aceita chaves:
     *  - interesse_atuar, descricao, deficiencia_ids[]
     */
    public function storeExperienciaPessoal(Request $request)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $experienciasPayload = $request->has('experiencias')
            ? $request->input('experiencias')
            : [$request->all()];

        if (!is_array($experienciasPayload)) {
            return response()->json(['message' => 'O campo experiencias deve ser um array de objetos.'], 422);
        }

        // Envolver em transação para garantir consistência
        $criadas = \Illuminate\Support\Facades\DB::transaction(function() use ($experienciasPayload, $candidato) {
            $criadas = [];

            foreach ($experienciasPayload as $expData) {
                // mapear chaves
                $mapped = [
                    'interesse_atuar' => array_key_exists('interesse_atuar', $expData)
                        ? (bool)$expData['interesse_atuar']
                        : false,
                    'descricao'       => $this->sanitizeHtml($expData['descricao'] ?? ''),
                    'deficiencia_ids' => $expData['deficiencia_ids'] ?? [],
                ];

                // validação por item
                $validated = validator($mapped, [
                    'interesse_atuar' => 'nullable|boolean',
                    'descricao'       => 'nullable|string|max:1000',
                    'deficiencia_ids' => 'nullable|array',
                    'deficiencia_ids.*' => 'integer|exists:deficiencias,id_deficiencia',
                ])->validate();

                $validated['id_candidato'] = $candidato->id;

                $experiencia = ExperienciaPessoal::create(collect($validated)->except('deficiencia_ids')->toArray());

                if (!empty($validated['deficiencia_ids'])) {
                    $experiencia->deficiencias()->sync($validated['deficiencia_ids']);
                }

                $criadas[] = $experiencia->load('deficiencias');
            }

            return $criadas;
        });

        return response()->json(count($criadas) === 1 ? $criadas[0] : $criadas, 201);
    }

    /**
     * Atualiza experiência pessoal do candidato.
     * PUT /candidatos/me/experiencias-pessoais/{id}
     */
    public function updateExperienciaPessoal(Request $request, $id)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $experiencia = ExperienciaPessoal::where('id_experiencia_pessoal', $id)
            ->where('id_candidato', $candidato->id)
            ->firstOrFail();

        // Mapear chaves
        $mapped = [
            'interesse_atuar' => $request->has('interesse_atuar')
                ? (bool)$request->input('interesse_atuar')
                : null,
            'descricao'       => $this->sanitizeHtml($request->input('descricao', '')),
            'deficiencia_ids' => $request->input('deficiencia_ids', []),
        ];

        // Validação
        $validated = validator($mapped, [
            'interesse_atuar' => 'nullable|boolean',
            'descricao'       => 'nullable|string|max:1000',
            'deficiencia_ids' => 'nullable|array',
            'deficiencia_ids.*' => 'integer|exists:deficiencias,id_deficiencia',
        ])->validate();

        // Envolver em transação para garantir consistência
        $experiencia = \Illuminate\Support\Facades\DB::transaction(function() use ($experiencia, $validated) {
            $experiencia->update(collect($validated)->except('deficiencia_ids')->toArray());

            if (array_key_exists('deficiencia_ids', $validated)) {
                $experiencia->deficiencias()->sync($validated['deficiencia_ids'] ?? []);
            }

            return $experiencia->load('deficiencias');
        });

        return response()->json($experiencia);
    }

    /** Remove experiência pessoal. */
    public function deleteExperienciaPessoal(Request $request, $id)
    {
        $user = $request->user();
        $candidato = Candidato::where('id_usuario', $user->id)->firstOrFail();

        $experiencia = ExperienciaPessoal::where('id_experiencia_pessoal', $id)
            ->where('id_candidato', $candidato->id)
            ->firstOrFail();

        $experiencia->delete();

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
        ], [
            'current_password.required' => 'Por favor, informe sua senha atual.',
            'password.required' => 'Por favor, informe sua nova senha.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.min' => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password.regex' => 'A nova senha deve conter ao menos uma letra e um número.',
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

        $request->validate(['password' => 'required|string'], [
            'password.required' => 'Por favor, informe sua senha para confirmar a exclusão.',
        ]);

        if (!Hash::check($request->input('password'), $user->senha_hash)) {
            return response()->json(['message' => 'Senha inválida.'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Conta removida com sucesso.']);
    }
}
