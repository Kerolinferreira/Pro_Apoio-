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
            ->with('endereco')
            ->firstOrFail();

        return response()->json($instituicao);
    }

    /**
     * Atualiza dados da instituição e do endereço (cria endereço se ausente).
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $instituicao = Instituicao::where('id_usuario', $user->id)->with('endereco')->firstOrFail();

        $data = $request->validate([
            // Instituição
            'cnpj'               => 'nullable|string',
            'razao_social'       => 'nullable|string',
            'nome_fantasia'      => 'nullable|string',
            'tipo_instituicao'   => 'nullable|string',
            'niveis_oferecidos'  => 'nullable', // aceita array ou json string
            'nome_responsavel'   => 'nullable|string',
            'funcao_responsavel' => 'nullable|string',
            'email_corporativo'  => 'nullable|email',
            'telefone_fixo'      => 'nullable|string',
            'celular_corporativo'=> 'nullable|string',
            'codigo_inep'        => 'nullable|string',

            // Endereço
            'cep'                => 'nullable|string',
            'logradouro'         => 'nullable|string',
            'bairro'             => 'nullable|string',
            'cidade'             => 'nullable|string',
            'estado'             => 'nullable|string|max:2',
            'numero'             => 'nullable|string',
            'complemento'        => 'nullable|string',
            'ponto_referencia'   => 'nullable|string',

            // Senha atual para validar alteração de dados sensíveis
            'current_password'   => 'required_with:cnpj,email_corporativo|string',
        ]);

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
            'cnpj','razao_social','nome_fantasia','tipo_instituicao','niveis_oferecidos',
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
        ]);

        $file = $request->file('logo');

        // Validação adicional de MIME type real (não apenas extensão)
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json(['message' => 'Tipo de arquivo inválido.'], 400);
        }

        // Remove logo antigo se existir
        if ($instituicao->logo_url) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($instituicao->logo_url);
        }

        // Gera nome seguro e único para o arquivo
        $extension = $file->getClientOriginalExtension();
        $filename = 'instituicao_' . $instituicao->id . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $extension;

        $path = $file->storeAs('logos-instituicoes', $filename, 'public');
        $instituicao->update(['logo_url' => $path]);

        return response()->json(['logo_url' => $path]);
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
}
