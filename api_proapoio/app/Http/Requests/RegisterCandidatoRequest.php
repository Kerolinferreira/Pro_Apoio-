<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para cadastro de candidatos.
 * Aceita chaves alternativas do front e aplica validações PT-BR.
 */
class RegisterCandidatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza e mapeia campos antes da validação:
     * - email: trim + lowercase
     * - escolaridade -> nivel_escolaridade
     * - nome_curso -> curso_superior
     * - nome_instituicao_ensino -> instituicao_ensino
     * - termos_aceite -> boolean
     */
    protected function prepareForValidation(): void
    {
        $payload = [
            'email'               => mb_strtolower(trim((string) $this->input('email'))),
            'nivel_escolaridade'  => $this->input('nivel_escolaridade', $this->input('escolaridade')),
            'curso_superior'      => $this->input('curso_superior', $this->input('nome_curso')),
            'instituicao_ensino'  => $this->input('instituicao_ensino', $this->input('nome_instituicao_ensino')),
            'termos_aceite'       => filter_var($this->input('termos_aceite', false), FILTER_VALIDATE_BOOLEAN),
        ];

        // Adicionar a normalização dos campos com máscara
        if ($this->has('cpf')) {
            $payload['cpf'] = preg_replace('/[^0-9]/', '', (string) $this->input('cpf'));
        }
        if ($this->has('cep')) {
            $payload['cep'] = preg_replace('/[^0-9]/', '', (string) $this->input('cep'));
        }
        if ($this->has('telefone')) {
            $payload['telefone'] = preg_replace('/[^0-9]/', '', (string) $this->input('telefone'));
        }

        $this->merge($payload);
    }

    /**
     * Regras de validação.
     * Observação: nomes de tabelas minúsculos (users, candidatos) para o unique/exists.
     */
    public function rules(): array
    {
        return [
            'nome'          => 'required|string|min:3',
            'email'         => 'required|email|unique:users,email',
            // mínimo 8, precisa de letra e número, e confirmação
            'password'      => ['required','min:8','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],

            // Campos com máscaras (pt-br-validator)
            'cpf'           => 'required|cpf|unique:candidatos,cpf',
            'cep'           => 'required|formato_cep',
            'telefone'      => 'required|telefone_com_ddd',

            // Endereço
            'logradouro'        => 'nullable|string',
            'bairro'            => 'nullable|string',
            'cidade'            => 'nullable|string',
            'estado'            => 'nullable|string|size:2',
            'numero'            => 'nullable|string',
            'complemento'       => 'nullable|string',
            'ponto_referencia'  => 'nullable|string',

            // Perfil acadêmico
            'nivel_escolaridade' => 'required|string',
            'curso_superior'     => 'required_if:nivel_escolaridade,Superior Incompleto,Superior Completo,Pós-Graduação,Mestrado,Doutorado',
            'instituicao_ensino' => 'required_with:curso_superior',

            // Experiência
            'experiencia'    => 'required|string|min:20',

            // Outros
            'link_perfil'    => 'nullable|url',
            'termos_aceite'  => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'                     => 'E-mail já cadastrado.',
            'password.confirmed'               => 'A confirmação de senha não confere.',
            'password.regex'                   => 'A senha deve conter ao menos uma letra e um número.',
            'cpf.cpf'                          => 'CPF inválido.',
            'cpf.unique'                       => 'CPF já cadastrado.',
            'cep.formato_cep'                  => 'CEP em formato inválido.',
            'telefone.telefone_com_ddd'        => 'Telefone em formato inválido.',
            'nivel_escolaridade.required'      => 'Informe o nível de escolaridade.',
            'curso_superior.required_if'       => 'O campo curso superior é obrigatório para o nível selecionado.',
            'instituicao_ensino.required_with' => 'Informe a instituição de ensino ao preencher o curso.',
            'experiencia.required'             => 'Informe sua experiência.',
            'experiencia.min'                  => 'A experiência deve ter no mínimo 20 caracteres.',
        ];
    }
}
