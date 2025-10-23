<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Vaga.
 *
 * Representa as oportunidades publicadas por instituições. Mantém
 * relacionamento com a instituição, propostas associadas e vagas salvas.
 */
class Vaga extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * A tabela foi renomeada para "Vagas" (inicial maiúscula) conforme
     * o esquema de dados definitivo.
     *
     * @var string
     */
    protected $table = 'Vagas';

    /**
     * Chave primária do modelo.
     *
     * A coluna id_vaga substitui a coluna id original.
     *
     * @var string
     */
    protected $primaryKey = 'id_vaga';

    protected $fillable = [
        'id_instituicao',
        'status',
        'aluno_nascimento_mes',
        'aluno_nascimento_ano',
        'necessidades_descricao',
        'carga_horaria_semanal',
        'regime_contratacao',
        'valor_remuneracao',
        'tipo_remuneracao',
        'titulo_vaga',
        'cidade',
        'estado',
    ];

    /**
     * Deficiências associadas à vaga. As deficiências foram normalizadas para
     * uma tabela separada com relação muitos-para-muitos.
     */
    public function deficiencias()
    {
        return $this->belongsToMany(Deficiencia::class, 'Vagas_Deficiencias', 'id_vaga', 'id_deficiencia');
    }

    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class, 'id_instituicao');
    }

    public function propostas()
    {
        return $this->hasMany(Proposta::class, 'id_vaga');
    }

    public function vagasSalvas()
    {
        return $this->hasMany(VagaSalva::class, 'id_vaga');
    }

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Permite acessar $vaga->id mesmo com a chave primária
     * renomeada para id_vaga.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}