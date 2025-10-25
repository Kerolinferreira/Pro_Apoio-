<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para Vagas Salvas.
 *
 * Armazena quais vagas foram marcadas como favoritas por um candidato.
 */
class VagaSalva extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * Renomeada para "Vagas_Salvas" (inicial maiúscula e separado por
     * sublinhado) conforme o esquema de dados definitivo.
     *
     * @var string
     */
    protected $table = 'Vagas_Salvas';

    /**
     * Chave primária.
     *
     * A coluna id_vaga_salva substitui o campo id original.
     *
     * @var string
     */
    protected $primaryKey = 'id_vaga_salva';

    public $timestamps = false;

    protected $fillable = [
        'id_candidato',
        'id_vaga',
    ];

    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'id_candidato');
    }

    public function vaga()
    {
        return $this->belongsTo(Vaga::class, 'id_vaga');
    }

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Permite acessar $vagaSalva->id mesmo com a chave primária
     * renomeada para id_vaga_salva.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}