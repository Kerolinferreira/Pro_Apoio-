<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instituicao;

class InstituicaoProfileController extends Controller
{
    /** Somente dígitos */
    private function onlyDigits(?string $v): ?string
    {
        if ($v === null) return null;
        return preg_replace('/\D+/', '', $v);
    }

    /**
     * Retorna o perfil da instituição logada.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $instituicao = Instituicao::where('id_usuario', $user->id)
            ->with(['endereco', 'vagas' => function($query) {
                $query->select('id_vaga', 'id_instituicao', 'titulo_vaga', 'cidade', 'regime_contratacao', 'status', 'created_at')
                      ->orderBy('created_at', 'desc');
            }])
            ->firstOrFail();

        // Adicionar email do usuário para compatibilidade com frontend
        $data = $instituicao->toArray();
        $data['email'] = $user->email;

        // Mapear vagas para formato esperado pelo frontend
        if (isset($data['vagas'])) {
            $data['vagas'] = array_map(function($vaga) {
                return [
                    'id' => $vaga['id_vaga'],
                    'titulo_vaga' => $vaga['titulo_vaga'],
                    'cidade' => $vaga['cidade'] ?? 'Não especificado',
                    'regime_contratacao' => $vaga['regime_contratacao'] ?? 'Não especificado',
                    'status' => $vaga['status'],
                    'data_publicacao' => $vaga['created_at'],
                ];
            }, $data['vagas']);
        }

        return response()->json($data);
    }

    /**
     * Atualiza dados da instituição e do endereço (cria endereço se ausente).
     */
    public function update(Request $request)
    {
        // Normaliza dados antes da validação
        if ($request->has('cnpj')) {
            $request->merge(['cnpj' => preg_replace('/\D+/', '', $request->input('cnpj'))]);
        }
        if ($request->has('telefone_fixo')) {
            $request->merge(['telefone_fixo' => preg_replace('/\D+/', '', $request->input('telefone_fixo'))]);
        }
        if ($request->has('celular_corporativo')) {
            $request->merge(['celular_corporativo' => preg_replace('/\D+/', '', $request->input('celular_corporativo'))]);
        }
        if ($request->has('cep')) {
            $request->merge(['cep' => preg_replace('/\D+/', '', $request->input('cep'))]);
        }

        $data = $request->validate([
            // Instituição
            'cnpj'               => 'nullable|string|min:14|max:14',
            'razao_social'       => 'nullable|string|max:255',
            'nome_fantasia'      => 'nullable|string|max:255',
            'descricao'          => 'nullable|string|max:1000',
            'tipo_instituicao'   => 'nullable|string|max:100',
            'niveis_oferecidos'  => 'nullable', // aceita array ou json string
            'nome_responsavel'   => 'nullable|string|max:255',
            'funcao_responsavel' => 'nullable|string|max:255',
            'email_corporativo'  => 'nullable|email|max:255',
            'telefone_fixo'      => 'nullable|string|min:10|max:11',
            'celular_corporativo'=> 'nullable|string|min:10|max:11',
            'codigo_inep'        => 'nullable|string|regex:/^\d{8}$/',

            // Endereço
            'cep'                => 'nullable|string|min:8|max:8',
            'logradouro'         => 'nullable|string|max:255',
            'bairro'             => 'nullable|string|max:100',
            'cidade'             => 'nullable|string|max:100',
            'estado'             => 'nullable|string|size:2',
            'numero'             => 'nullable|string|max:10',
            'complemento'        => 'nullable|string|max:100',
            'ponto_referencia'   => 'nullable|string|max:255',

            // Senha atual para validar alteração de dados sensíveis
            'current_password'   => 'required_with:cnpj,email_corporativo|string',
        ], [
            // Mensagens de validação da instituição
            'cnpj.min' => 'O CNPJ deve ter 14 dígitos.',
            'cnpj.max' => 'O CNPJ deve ter 14 dígitos.',
            'razao_social.max' => 'A razão social não pode ter mais de 255 caracteres.',
            'nome_fantasia.max' => 'O nome fantasia não pode ter mais de 255 caracteres.',
            'tipo_instituicao.max' => 'O tipo de instituição não pode ter mais de 100 caracteres.',
            'nome_responsavel.max' => 'O nome do responsável não pode ter mais de 255 caracteres.',
            'funcao_responsavel.max' => 'A função do responsável não pode ter mais de 255 caracteres.',
            'email_corporativo.email' => 'O e-mail corporativo deve ser um endereço válido (ex: contato@instituicao.com.br).',
            'email_corporativo.max' => 'O e-mail corporativo não pode ter mais de 255 caracteres.',
            'telefone_fixo.min' => 'O telefone fixo deve ter no mínimo 10 dígitos.',
            'telefone_fixo.max' => 'O telefone fixo deve ter no máximo 11 dígitos.',
            'celular_corporativo.min' => 'O celular corporativo deve ter no mínimo 10 dígitos.',
            'celular_corporativo.max' => 'O celular corporativo deve ter no máximo 11 dígitos.',
            'codigo_inep.regex' => 'O código INEP deve conter exatamente 8 dígitos numéricos.',

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

            // Mensagem de validação de senha
            'current_password.required_with' => 'Para alterar o CNPJ ou e-mail corporativo, você precisa informar sua senha atual.',
        ]);

        try {
            $user = $request->user();
            $instituicao = Instituicao::where('id_usuario', $user->id)->with('endereco')->firstOrFail();

        // Validar senha atual se estiver alterando CNPJ ou email corporativo
        if (isset($data['current_password'])) {
            if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->senha_hash)) {
                return response()->json(['message' => 'Senha incorreta'], 400);
            }
        }

        // Normalizações de dígitos
        if (isset($data['cnpj']))               $data['cnpj']               = $this->onlyDigits($data['cnpj']);
        if (isset($data['telefone_fixo']))      $data['telefone_fixo']      = $this->onlyDigits($data['telefone_fixo']);
        if (isset($data['celular_corporativo']))$data['celular_corporativo']= $this->onlyDigits($data['celular_corporativo']);
        if (isset($data['cep']))                $data['cep']                = $this->onlyDigits($data['cep']);

        // CORREÇÃO: Validar unicidade de CNPJ se estiver sendo alterado (Falha #4)
        if (isset($data['cnpj']) && $data['cnpj'] !== $instituicao->cnpj) {
            $cnpjExistente = Instituicao::where('cnpj', $data['cnpj'])
                ->where('id_instituicao', '!=', $instituicao->id_instituicao)
                ->exists();

            if ($cnpjExistente) {
                return response()->json([
                    'message' => 'CNPJ já cadastrado por outra instituição.',
                    'errors' => ['cnpj' => ['Este CNPJ já está em uso.']]
                ], 422);
            }
        }

        // niveis_oferecidos: aceita array ou JSON string
        if (isset($data['niveis_oferecidos'])) {
            if (is_string($data['niveis_oferecidos'])) {
                $decoded = json_decode($data['niveis_oferecidos'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['niveis_oferecidos'] = $decoded;
                } // se JSON inválido, mantém string como foi enviada
            }
        }

        // Atualiza campos próprios
        $institPayload = collect($data)->only([
            'cnpj','razao_social','nome_fantasia','descricao','tipo_instituicao','niveis_oferecidos',
            'nome_responsavel','funcao_responsavel','email_corporativo',
            'telefone_fixo','celular_corporativo','codigo_inep',
        ])->toArray();

        $instituicao->update($institPayload);

        // Endereço: cria ou atualiza se veio algo
        $enderecoPayload = collect($data)->only([
            'cep','logradouro','bairro','cidade','estado','numero','complemento','ponto_referencia',
        ])->filter();

        if ($enderecoPayload->isNotEmpty()) {
            if ($instituicao->endereco) {
                $instituicao->endereco->update($enderecoPayload->toArray());
            } else {
                $novo = $instituicao->endereco()->create($enderecoPayload->toArray());
                $instituicao->id_endereco = $novo->id;
                $instituicao->save();
            }
        }

            $instituicao->load('endereco');

            return response()->json($instituicao);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Instituição não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao atualizar perfil de instituição: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload do logo da instituição (até 2MB).
     * Salva o caminho em logo_url com validação robusta de segurança.
     */
    public function uploadLogo(Request $request)
    {
        $user = $request->user();
        $instituicao = Instituicao::where('id_usuario', $user->id)->firstOrFail();

        // Validação robusta de imagem
        $request->validate([
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
            ],
        ], [
            'logo.required' => 'Por favor, selecione um logo.',
            'logo.file' => 'O arquivo enviado não é válido.',
            'logo.image' => 'O arquivo deve ser uma imagem.',
            'logo.mimes' => 'O logo deve ser nos formatos: JPEG, JPG, PNG ou WEBP.',
            'logo.max' => 'O logo não pode ser maior que 2MB.',
            'logo.dimensions' => 'O logo deve ter no mínimo 100x100 pixels e no máximo 4000x4000 pixels.',
        ]);

        $file = $request->file('logo');

        // Validação adicional de MIME type real (não apenas extensão)
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json(['message' => 'Tipo de arquivo inválido.'], 400);
        }

        // Remove logo antigo se existir
        if ($instituicao->logo_url) {
            // CORREÇÃO P21: Remove /storage/ prefix se presente
            $oldPath = str_replace('/storage/', '', $instituicao->logo_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }

        // Gera nome seguro e único para o arquivo
        $extension = $file->getClientOriginalExtension();
        $filename = 'instituicao_' . $instituicao->id_instituicao . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $extension;

        $path = $file->storeAs('logos-instituicoes', $filename, 'public');

        // CORREÇÃO P21: Retornar URL completa acessível pelo frontend
        $url = '/storage/' . $path;
        $instituicao->update(['logo_url' => $url]);

        return response()->json(['logo_url' => $url]);
    }

    /**
     * Troca de senha do usuário autenticado (instituição).
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            // mínimo 8, letras e números
            'password'         => ['required','string','confirmed','min:8','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->senha_hash)) {
            return response()->json(['message' => 'Senha atual incorreta.'], 400);
        }

        $user->update(['senha_hash' => \Illuminate\Support\Facades\Hash::make($data['password'])]);

        return response()->json(['message' => 'Senha atualizada com sucesso.']);
    }

    /**
     * Exibe informações públicas de uma instituição.
     * Sem dados sensíveis.
     */
    public function showPublic($id)
    {
        $instituicao = \App\Models\Instituicao::with('endereco')->findOrFail($id);

        return response()->json([
            'id'            => $instituicao->id,
            'razao_social'  => $instituicao->razao_social,
            'nome_fantasia' => $instituicao->nome_fantasia,
            'codigo_inep'   => $instituicao->codigo_inep,
            'cidade'        => optional($instituicao->endereco)->cidade,
            'estado'        => optional($instituicao->endereco)->estado,
        ]);
    }

    /**
     * Exclui a conta da instituição autenticada.
     * Direito ao esquecimento (LGPD/GDPR).
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $request->validate(['password' => 'required|string'], [
            'password.required' => 'Por favor, informe sua senha para confirmar a exclusão.',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), $user->senha_hash)) {
            return response()->json(['message' => 'Senha inválida.'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Conta removida com sucesso.']);
    }
}
