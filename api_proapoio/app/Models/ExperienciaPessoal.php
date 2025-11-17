<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Experiência Pessoal.
 */
class ExperienciaPessoal extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * No esquema definitivo, a tabela de experiências pessoais foi
     * renomeada para "Experiencias_Pessoais" (inicial maiúscula e
     * sublinhado). Declarar explicitamente a propriedade $table garante
     * a utilização da nomenclatura correta.
     *
     * @var string
     */
    protected $table = 'experiencias_pessoais';

    /**
     * Chave primária do modelo.
     *
     * A coluna id_experiencia_pessoal substitui a coluna id.
     *
     * @var string
     */
    protected $primaryKey = 'id_experiencia_pessoal';

    protected $fillable = [
        // Identificador do candidato associado
        'id_candidato',
        // Campos definidos no esquema
        // Interesse em atuar em vaga semelhante (boolean)
        'interesse_atuar',
        // Descrição da experiência pessoal
        'descricao',
    ];

    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'id_candidato');
    }

    /**
     * CORREÇÃO P6: Relação muitos-para-muitos entre experiências pessoais e deficiências.
     * Permite associar deficiências a experiências pessoais do candidato.
     */
    public function deficiencias()
    {
        return $this->belongsToMany(
            Deficiencia::class,
            'experiencias_pessoais_deficiencias',
            'id_experiencia_pessoal',
            'id_deficiencia'
        );
    }

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Permite que $experiencia->id retorne o valor de
     * id_experiencia_pessoal após a renomeação da chave primária.
     *
     * @return mixed
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}