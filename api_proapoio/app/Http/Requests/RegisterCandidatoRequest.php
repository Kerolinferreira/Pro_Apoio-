<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
     * - password -> senha (aceita ambos)
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

        // Mapear password para senha (aceita ambos)
        if ($this->has('senha')) {
            $payload['senha'] = $this->input('senha');
            $payload['senha_confirmation'] = $this->input('senha_confirmation');
        } elseif ($this->has('password')) {
            $payload['senha'] = $this->input('password');
            $payload['senha_confirmation'] = $this->input('password_confirmation');
        }

        // Adicionar a normalização dos campos com máscara
        // Nota: telefone não é normalizado aqui pois o validador telefone_com_ddd espera a máscara
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
     * Observação: nomes de tabelas minúsculos (usuarios, candidatos) para o unique/exists.
     */
    public function rules(): array
    {
        return [
            'nome'          => 'required|string|min:3',
            'nome_completo' => 'nullable|string|min:3',
            'email'         => 'required|email|unique:usuarios,email',
            // mínimo 8, precisa de letra e número, e confirmação
            'senha'         => ['required','min:8','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],

            // Campos com máscaras (pt-br-validator)
            'cpf'           => 'required|cpf|unique:candidatos,cpf',
            'data_nascimento' => 'required|date|before:-18 years',
            'cep'           => 'required|digits:8',
            'telefone'      => 'required|digits_between:10,11',
            // Endereço
            'logradouro'        => 'nullable|string',
            'bairro'            => 'nullable|string',
            'cidade'            => 'nullable|string',
            'estado'            => 'nullable|string|size:2',

            // Perfil acadêmico
            'nivel_escolaridade' => 'required|string',
            'curso_superior'     => 'required_if:nivel_escolaridade,Superior Incompleto,Superior Completo,Pós-Graduação,Mestrado,Doutorado',
            'instituicao_ensino' => 'required_with:curso_superior',

            // Deficiências (experiência com deficiências)
            'deficiencia_ids'   => 'nullable|array',
            'deficiencia_ids.*' => 'integer|exists:deficiencias,id_deficiencia',

            // Outros (o campo 'experiencia' foi removido pois não era persistido)
            'link_perfil'   => 'nullable|url',
            'termos_aceite' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'                    => 'Por favor, informe seu nome.',
            'nome.min'                         => 'O nome deve ter no mínimo 3 caracteres.',
            'email.required'                   => 'Por favor, informe seu e-mail.',
            'email.email'                      => 'Por favor, informe um e-mail válido.',
            'email.unique'                     => 'E-mail já cadastrado.',
            'senha.required'                   => 'Por favor, informe uma senha.',
            'senha.min'                        => 'A senha deve ter no mínimo 8 caracteres.',
            'senha.confirmed'                  => 'A confirmação de senha não confere.',
            'senha.regex'                      => 'A senha deve conter ao menos uma letra e um número.',
            'cpf.required'                     => 'Por favor, informe seu CPF.',
            'cpf.cpf'                          => 'CPF inválido.',
            'cpf.unique'                       => 'CPF já cadastrado.',
            'data_nascimento.required'         => 'Por favor, informe sua data de nascimento.',
            'data_nascimento.date'             => 'Data de nascimento inválida.',
            'data_nascimento.before'           => 'Você deve ter pelo menos 18 anos para se cadastrar.',
            'cep.required'                     => 'Por favor, informe o CEP.',
            'cep.digits'                       => 'CEP em formato inválido.',
            'telefone.required'                => 'Por favor, informe seu telefone.',
            'telefone.digits_between'          => 'Telefone em formato inválido.',
            'nivel_escolaridade.required'      => 'Informe o nível de escolaridade.',
            'curso_superior.required_if'       => 'O campo curso superior é obrigatório para o nível selecionado.',
            'instituicao_ensino.required_with' => 'Informe a instituição de ensino ao preencher o curso.',
            'deficiencia_ids.array'            => 'Deficiências devem ser uma lista válida.',
            'deficiencia_ids.*.integer'        => 'ID de deficiência inválido.',
            'deficiencia_ids.*.exists'         => 'Uma ou mais deficiências selecionadas não existem.',
            'link_perfil.url'                  => 'O link do perfil deve ser uma URL válida.',
            'termos_aceite.boolean'            => 'O aceite dos termos é inválido.',
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