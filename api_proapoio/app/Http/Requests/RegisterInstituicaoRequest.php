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
     * - cnpj: remove máscara
     * - cep: remove máscara
     * - niveis_oferecidos: converte array em JSON string, se necessário
     */
    protected function prepareForValidation(): void
    {
        $payload = [
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ];

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
            'nome'                    => 'required|string|min:3|max:255',
            'email'                   => 'required|email|unique:users,email',
            // Requer confirmed e a regex para forçar complexidade mínima
            'password'                => ['required','min:8','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],

            // CNPJ: unique e custom rule 'cnpj'. Min/Max de 14 dígitos (após normalização)
            'cnpj'                    => 'required|string|cnpj|unique:instituicoes,cnpj|min:14|max:14',
            'razao_social'            => 'required|string|max:255',
            'nome_fantasia'           => 'required|string|max:255',

            // Código INEP pode ser opcional; quando presente, 8 dígitos
            'codigo_inep'             => ['nullable','string','regex:/^\d{8}$/'],

            // Endereço - CEP min:8 e max:8 (após normalização)
            'cep'                     => 'required|formato_cep|min:8|max:8',
            'logradouro'              => 'required|string|max:255',
            'bairro'                  => 'required|string|max:255',
            'cidade'                  => 'required|string|max:255',
            'estado'                  => 'required|string|size:2',
            'numero'                  => 'required|string|max:10',
            'complemento'             => 'nullable|string|max:255',
            'ponto_referencia'        => 'nullable|string|max:255',

            // Contatos e metadados
            'telefone_fixo'           => 'nullable|telefone_com_ddd',
            'celular_corporativo'     => 'nullable|celular_com_ddd',
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
            'email.unique'                    => 'E-mail já cadastrado.',
            'password.confirmed'              => 'A confirmação de senha não confere.',
            'password.regex'                  => 'A senha deve conter ao menos uma letra e um número.',
            'cnpj.cnpj'                       => 'CNPJ inválido.',
            'cnpj.unique'                     => 'CNPJ já cadastrado.',
            'cnpj.required'                   => 'O campo CNPJ é obrigatório.',
            'cep.required'                    => 'O campo CEP é obrigatório.',
            'cep.formato_cep'                 => 'CEP em formato inválido.',
            'telefone_fixo.telefone_com_ddd'  => 'Telefone fixo em formato inválido.',
            'celular_corporativo.celular_com_ddd' => 'Celular em formato inválido.',
            'codigo_inep.regex'               => 'O código INEP deve conter 8 dígitos.',
            'niveis_oferecidos.required'      => 'O campo níveis oferecidos é obrigatório.',
            'niveis_oferecidos.json'          => 'O campo níveis oferecidos deve ser um JSON válido.',
            'termos_aceite.required'          => 'Você precisa aceitar os termos de uso para se cadastrar.',
            'termos_aceite.accepted'          => 'Você precisa aceitar os termos de uso para se cadastrar.',
        ];
    }
}
