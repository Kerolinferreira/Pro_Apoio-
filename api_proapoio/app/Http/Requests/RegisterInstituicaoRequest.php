<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use LaravelLegends\PtBrValidator\Rules\Celular;

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
     * - cnpj: remove máscara
     * - cep: remove máscara
     * - niveis_oferecidos: converte array em JSON string, se necessário
     * - password -> senha (aceita ambos)
     */
    protected function prepareForValidation(): void
    {
        $payload = [
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ];

        // Mapear password para senha (aceita ambos)
        if ($this->has('senha')) {
            $payload['senha'] = $this->input('senha');
            $payload['senha_confirmation'] = $this->input('senha_confirmation');
        } elseif ($this->has('password')) {
            $payload['senha'] = $this->input('password');
            $payload['senha_confirmation'] = $this->input('password_confirmation');
        }

        // 1. Normaliza CNPJ (remove pontos, traços, barras)
        if ($this->has('cnpj')) {
            $payload['cnpj'] = preg_replace('/[^0-9]/', '', (string) $this->input('cnpj'));
        }

        // 2. Normaliza CEP (remove pontos, traços)
        if ($this->has('cep')) {
            $payload['cep'] = preg_replace('/[^0-9]/', '', (string) $this->input('cep'));
        }

        // 3. Normaliza Níveis Oferecidos (garante que é uma string JSON se for um array)
        if ($this->has('niveis_oferecidos')) {
            $niv = $this->input('niveis_oferecidos');
            // Se for um array, codifica para JSON.
            if (is_array($niv)) {
                $payload['niveis_oferecidos'] = json_encode($niv, JSON_UNESCAPED_UNICODE);
            // Se já for uma string, decodifica e recodifica para garantir a formatação correta.
            } elseif (is_string($niv) && ($decoded = json_decode($niv, true)) !== null) {
                // Isso garante que a string final esteja sempre no formato esperado (sem escapes desnecessários).
                $payload['niveis_oferecidos'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }
        if ($this->has('telefone')) {
            $payload['telefone'] = preg_replace('/[^0-9]/', '', (string) $this->input('telefone'));
        }
        // Normaliza telefone_fixo (remove formatação)
        if ($this->has('telefone_fixo')) {
            $payload['telefone_fixo'] = preg_replace('/[^0-9]/', '', (string) $this->input('telefone_fixo'));
        }
        // Normaliza celular_corporativo (remove formatação)
        if ($this->has('celular_corporativo')) {
            $payload['celular_corporativo'] = preg_replace('/[^0-9]/', '', (string) $this->input('celular_corporativo'));
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
            'nome'                    => 'required|string|min:3|max:255',
            'email'                   => 'required|email|unique:usuarios,email',
            // Requer confirmed e a regex para forçar complexidade mínima
            'senha'                   => ['required','min:8','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],

            // CNPJ: unique e custom rule 'cnpj'. Min/Max de 14 dígitos (após normalização)
            'cnpj'                    => 'required|string|cnpj|unique:instituicoes,cnpj|min:14|max:14',
            'razao_social'            => 'required|string|max:255',
            'nome_fantasia'           => 'required|string|max:255',

            // Código INEP pode ser opcional; quando presente, 8 dígitos
            'codigo_inep'             => ['nullable','string','regex:/^\d{8}$/'],

            // Endereço - CEP min:8 e max:8 (após normalização)
            'cep'           => 'required|digits:8',
            'logradouro'              => 'required|string|max:255',
            'bairro'                  => 'required|string|max:255',
            'cidade'                  => 'required|string|max:255',
            'estado'                  => 'required|string|size:2',
            'numero'                  => 'required|string|max:10',
            'complemento'             => 'nullable|string|max:255',
            'ponto_referencia'        => 'nullable|string|max:255',

            // Contatos e metadados
            'telefone_fixo'      => 'nullable|digits_between:10,11',
            'celular_corporativo'      => 'nullable|digits_between:10,11',
            'email_corporativo'       => 'nullable|email|max:255',

            'tipo_instituicao'        => 'required|string|max:50', // Definido como obrigatório

            // CORREÇÃO: Mantido como 'required|json' e validamos no prepareForValidation
            'niveis_oferecidos'       => 'required|json',

            'nome_responsavel'        => 'required|string|max:255', // Definido como obrigatório
            'funcao_responsavel'      => 'required|string|max:255', // Definido como obrigatório

            // CORREÇÃO: Deve ser 'required|accepted' para garantir que foi marcado
            'termos_aceite'           => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'                   => 'Por favor, informe o nome da instituição.',
            'nome.min'                        => 'O nome deve ter no mínimo 3 caracteres.',
            'email.required'                  => 'Por favor, informe o e-mail.',
            'email.email'                     => 'Por favor, informe um e-mail válido.',
            'email.unique'                    => 'E-mail já cadastrado.',
            'senha.required'                  => 'Por favor, informe uma senha.',
            'senha.min'                       => 'A senha deve ter no mínimo 8 caracteres.',
            'senha.confirmed'                 => 'A confirmação de senha não confere.',
            'senha.regex'                     => 'A senha deve conter ao menos uma letra e um número.',
            'cnpj.required'                   => 'Por favor, informe o CNPJ.',
            'cnpj.cnpj'                       => 'CNPJ inválido.',
            'cnpj.unique'                     => 'CNPJ já cadastrado.',
            'razao_social.required'           => 'Por favor, informe a razão social.',
            'nome_fantasia.required'          => 'Por favor, informe o nome fantasia.',
            'cep.required'                    => 'Por favor, informe o CEP.',
            'cep.digits'                      => 'CEP em formato inválido.',
            'logradouro.required'             => 'Por favor, informe o logradouro.',
            'bairro.required'                 => 'Por favor, informe o bairro.',
            'cidade.required'                 => 'Por favor, informe a cidade.',
            'estado.required'                 => 'Por favor, informe o estado.',
            'estado.size'                     => 'O estado deve ter 2 caracteres.',
            'numero.required'                 => 'Por favor, informe o número.',
            'telefone_fixo.digits_between'    => 'Telefone fixo em formato inválido.',
            'celular_corporativo.digits_between'  => 'Celular corporativo em formato inválido.',
            'tipo_instituicao.required'       => 'Por favor, informe o tipo de instituição.',
            'niveis_oferecidos.required'      => 'Por favor, informe os níveis oferecidos.',
            'niveis_oferecidos.json'          => 'O campo níveis oferecidos deve ser um JSON válido.',
            'nome_responsavel.required'       => 'Por favor, informe o nome do responsável.',
            'funcao_responsavel.required'     => 'Por favor, informe a função do responsável.',
            'codigo_inep.regex'               => 'O código INEP deve conter 8 dígitos.',
            'termos_aceite.required'          => 'Você precisa aceitar os termos de uso para se cadastrar.',
            'termos_aceite.accepted'          => 'Você precisa aceitar os termos de uso para se cadastrar.',
        ];
    }

    /**
     * Sobrescreve o tratamento de validação falha para retornar JSON detalhado.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Erro de validação. Verifique os campos e tente novamente.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
