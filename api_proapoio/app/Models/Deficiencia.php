<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Deficiência.
 *
 * Representa um tipo de deficiência cadastrado no sistema. Este modelo
 * se relaciona com experiências profissionais e vagas por meio de tabelas
 * pivot, permitindo que múltiplas deficiências sejam associadas a
 * diferentes entidades.
 */
class Deficiencia extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * Conforme o esquema de dados, a tabela foi renomeada para
     * "Deficiencias" (inicial maiúscula).
     *
     * @var string
     */
    protected $table = 'deficiencias';

    /**
     * Chave primária da tabela.
     *
     * O campo id_deficiencia substitui a coluna id original.
     *
     * @var string
     */
    protected $primaryKey = 'id_deficiencia';

    protected $fillable = [
        'nome',
    ];

    public $timestamps = false;

    /**
     * Relação com experiências profissionais.
     */
    public function experienciasProfissionais()
    {
        return $this->belongsToMany(ExperienciaProfissional::class, 'experiencias_profissionais_deficiencias', 'id_deficiencia', 'id_experiencia_profissional');
    }

    /**
     * Relação com vagas.
     */
    public function vagas()
    {
        return $this->belongsToMany(Vaga::class, 'vagas_deficiencias', 'id_deficiencia', 'id_vaga');
    }

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Permite que $deficiencia->id continue retornando o valor de
     * id_deficiencia após a renomeação da chave primária.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}