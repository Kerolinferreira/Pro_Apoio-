<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExperienciaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização será feita no controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idade_aluno'                 => 'nullable|integer|min:0|max:120',
            'tempo_experiencia'           => 'nullable|string|max:255',
            'tempo'                       => 'nullable|string|max:255', // Alias
            'interesse_mesma_deficiencia' => 'nullable|boolean',
            'candidatar_mesma_deficiencia'=> 'nullable|boolean', // Alias do front
            'descricao'                   => 'nullable|string|max:1000',
            'comentario'                  => 'nullable|string|max:1000', // Alias do front
            'deficiencia_ids'             => 'nullable|array',
            'deficiencia_ids.*'           => 'integer|exists:deficiencias,id_deficiencia',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'idade_aluno.integer' => 'A idade do aluno deve ser um número inteiro.',
            'idade_aluno.min' => 'A idade do aluno deve ser no mínimo 0.',
            'idade_aluno.max' => 'A idade do aluno não pode ser maior que 120.',
            'tempo_experiencia.max' => 'O tempo de experiência não pode ter mais de 255 caracteres.',
            'tempo.max' => 'O tempo de experiência não pode ter mais de 255 caracteres.',
            'interesse_mesma_deficiencia.boolean' => 'O campo de interesse deve ser verdadeiro ou falso.',
            'candidatar_mesma_deficiencia.boolean' => 'O campo de interesse deve ser verdadeiro ou falso.',
            'descricao.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            'comentario.max' => 'O comentário não pode ter mais de 1000 caracteres.',
            'deficiencia_ids.array' => 'As deficiências devem ser um array.',
            'deficiencia_ids.*.integer' => 'Cada deficiência deve ser um número inteiro.',
            'deficiencia_ids.*.exists' => 'Uma ou mais deficiências selecionadas não existem.',
        ];
    }
}
