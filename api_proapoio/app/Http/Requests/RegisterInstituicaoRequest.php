<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para cadastro de instituições.
 * Normaliza e valida campos com pt-br-validator.
 */
class RegisterInstituicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalizações antes da validação:
     * - email: trim + lowercase
     * - niveis_oferecidos: aceita array; se vier array, converte para JSON string
     */
    protected function prepareForValidation(): void
    {
        $payload = [
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ];

        if ($this->has('niveis_oferecidos')) {
            $niv = $this->input('niveis_oferecidos');
            if (is_array($niv)) {
                $payload['niveis_oferecidos'] = json_encode($niv, JSON_UNESCAPED_UNICODE);
            }
        }

        $this->merge($payload);
    }

    /**
     * Regras.
     * Observação: use nomes de tabelas minúsculos nas regras unique/exists.
     */
    public function rules(): array
    {
        return [
            'nome'                 => 'required|string|min:3',
            'email'                => 'required|email|unique:users,email',
            'password'             => ['required','min:8','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],

            'cnpj'                 => 'required|cnpj|unique:instituicoes,cnpj',
            'razao_social'         => 'required|string',
            'nome_fantasia'        => 'required|string',

            // Código INEP pode ser opcional; quando presente, 8 dígitos
            'codigo_inep'          => ['nullable','string','regex:/^\d{8}$/'],

            // Endereço
            'cep'                  => 'nullable|formato_cep',
            'logradouro'           => 'nullable|string',
            'bairro'               => 'nullable|string',
            'cidade'               => 'nullable|string',
            'estado'               => 'nullable|string|size:2',
            'numero'               => 'nullable|string',
            'complemento'          => 'nullable|string',
            'ponto_referencia'     => 'nullable|string',

            // Contatos e metadados
            'telefone_fixo'        => 'nullable|telefone_com_ddd',
            'celular_corporativo'  => 'nullable|celular_com_ddd',
            'email_corporativo'    => 'nullable|email',

            'tipo_instituicao'     => 'nullable|string',

            // Aceita string JSON; se vier array, é convertido em prepareForValidation
            'niveis_oferecidos'    => 'nullable|json',

            'nome_responsavel'     => 'nullable|string',
            'funcao_responsavel'   => 'nullable|string',

            'termos_aceite'        => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'                           => 'E-mail já cadastrado.',
            'password.confirmed'                     => 'A confirmação de senha não confere.',
            'password.regex'                         => 'A senha deve conter ao menos uma letra e um número.',
            'cnpj.cnpj'                              => 'CNPJ inválido.',
            'cnpj.unique'                            => 'CNPJ já cadastrado.',
            'cep.formato_cep'                        => 'CEP em formato inválido.',
            'telefone_fixo.telefone_com_ddd'         => 'Telefone fixo em formato inválido.',
            'celular_corporativo.celular_com_ddd'    => 'Celular em formato inválido.',
            'codigo_inep.regex'                      => 'O código INEP deve conter 8 dígitos.',
            'niveis_oferecidos.json'                 => 'O campo níveis oferecidos deve ser um JSON válido.',
        ];
    }
}
