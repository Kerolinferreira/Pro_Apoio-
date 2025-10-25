<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent para a tabela de propostas.
 *
 * Representa as propostas trocadas entre candidatos e instituições.
 *
 * Os atributos disponíveis seguem o esquema definitivo: id_vaga,
 * id_candidato, iniciador, mensagem, status, data_envio, data_resposta
 * e mensagem_resposta. As relações (vaga e candidato) estão
 * implementadas logo abaixo.
 */
class Proposta extends Model
{
    use HasFactory;

    /**
     * Atributos preenchíveis em massa.
     *
     * @var array<int, string>
     */
    /**
     * Nome da tabela associada ao modelo.
     *
     * Conforme o esquema de dados, a tabela de propostas foi renomeada
     * para "Propostas" com inicial maiúscula.
     *
     * @var string
     */
    protected $table = 'Propostas';

    /**
     * Chave primária do modelo.
     *
     * A coluna id_proposta substitui a coluna id original.
     *
     * @var string
     */
    protected $primaryKey = 'id_proposta';

    protected $fillable = [
        'id_vaga',
        'id_candidato',
        'iniciador',
        'mensagem',
        'status',
        'data_envio',
        'data_resposta',
        'mensagem_resposta',
    ];

    /**
     * Vaga associada à proposta.
     */
    public function vaga()
    {
        return $this->belongsTo(Vaga::class, 'id_vaga');
    }

    /**
     * Candidato associado à proposta.
     */
    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'id_candidato');
    }

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Permite acessar $proposta->id mesmo com a chave primária
     * renomeada para id_proposta.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}