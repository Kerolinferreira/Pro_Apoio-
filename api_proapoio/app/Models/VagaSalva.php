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
    protected $table = 'vagas_salvas';

    /**
     * Chave primária.
     *
     * A coluna id_vaga_salva substitui o campo id original.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_candidato',
        'id_vaga',
    ];

    /**
     * Atributos que devem ser visíveis na serialização JSON.
     *
     * @var array
     */
    protected $visible = [
        'id',
        'id_candidato',
        'id_vaga',
        'vaga',
        'created_at',
        'updated_at',
    ];

    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'id_candidato', 'id_candidato');
    }

    public function vaga()
    {
        return $this->belongsTo(Vaga::class, 'id_vaga', 'id_vaga');
    }
}