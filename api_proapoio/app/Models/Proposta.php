<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Vaga;

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
    protected $table = 'propostas';

    /**
     * Chave primária do modelo.
     *
     * A coluna id_proposta substitui a coluna id original.
     *
     * @var string
     */
    protected $primaryKey = 'id_proposta';

    /**
     * Valores padrão para os atributos.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'ENVIADA',
    ];

    protected $fillable = [
        'id_vaga',
        'id_candidato',
        'iniciador',
        'mensagem',
        'status',
        'data_envio',
        'data_resposta',
        'mensagem_resposta',
        'id_remetente',
        'tipo_remetente',
        'id_destinatario',
        'tipo_destinatario',
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
     * Remetente da proposta (relacionamento polimórfico).
     * Pode ser um Candidato ou uma Instituicao.
     */
    public function remetente()
    {
        return $this->morphTo(null, 'tipo_remetente', 'id_remetente');
    }

    /**
     * Destinatário da proposta (relacionamento polimórfico).
     * Pode ser um Candidato ou uma Instituicao.
     */
    public function destinatario()
    {
        return $this->morphTo(null, 'tipo_destinatario', 'id_destinatario');
    }

    /**
     * Casts dos atributos.
     */
    protected $casts = [
        'status' => \App\Enums\PropostaStatus::class,
        'data_envio' => 'datetime',
        'data_resposta' => 'datetime',
        'data_criacao' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    /**
     * Mutator para garantir que o status seja sempre salvo em maiúsculas.
     * Necessário porque SQLite não suporta ENUM nativamente.
     */
    public function setStatusAttribute($value)
    {
        if ($value instanceof \App\Enums\PropostaStatus) {
            $this->attributes['status'] = $value->value;
        } elseif (is_string($value)) {
            // Garantir maiúsculas para compatibilidade com enum
            $this->attributes['status'] = strtoupper($value);
        } else {
            $this->attributes['status'] = $value;
        }
    }
}