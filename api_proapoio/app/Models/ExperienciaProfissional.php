<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Experiência Profissional.
 */
class ExperienciaProfissional extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * No esquema definitivo, a tabela de experiências profissionais foi
     * renomeada para "Experiencias_Profissionais" (inicial maiúscula e
     * com sublinhado). Declarar explicitamente o nome da tabela garante
     * que o Eloquent utilize a nomenclatura correta.
     *
     * @var string
     */
    protected $table = 'experiencias_profissionais';

    /**
     * Chave primária do modelo.
     *
     * A coluna id_experiencia_profissional substitui a coluna id
     * originalmente utilizada. Especificar o nome da chave primária
     * mantém a compatibilidade com o esquema de dados.
     *
     * @var string
     */
    protected $primaryKey = 'id_experiencia_profissional';

    protected $fillable = [
        // Identificador do candidato associado
        'id_candidato',
        // Demais dados da experiência
        'idade_aluno',
        'tempo_experiencia',
        // De acordo com o esquema, indica se deseja atuar com a mesma deficiência
        'interesse_mesma_deficiencia',
        // Descrição da experiência profissional
        'descricao',
    ];

    /**
     * Relação muitos-para-muitos entre experiências profissionais e deficiências.
     * A coluna tipo_deficiencia foi removida e substituída por uma tabela
     * pivot experiencias_profissionais_deficiencias.
     */
    public function deficiencias()
    {
        // Relação muitos-para-muitos utilizando a tabela pivô
        return $this->belongsToMany(
            Deficiencia::class,
            'experiencias_profissionais_deficiencias',
            'id_experiencia_profissional',
            'id_deficiencia'
        );
    }

    public function candidato()
    {
        // A chave estrangeira foi renomeada para id_candidato
        return $this->belongsTo(Candidato::class, 'id_candidato');
    }

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Permite que $experiencia->id retorne o valor de
     * id_experiencia_profissional após a renomeação da chave primária.
     *
     * @return mixed
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}