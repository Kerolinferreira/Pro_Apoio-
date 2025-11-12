<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Vaga.
 *
 * Representa as oportunidades publicadas por instituições. Mantém
 * relacionamento com a instituição, propostas associadas e vagas salvas.
 *
 * Usa SoftDeletes para permitir recuperação de vagas excluídas acidentalmente.
 */
class Vaga extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela associada ao modelo.
     *
     * A tabela foi renomeada para "Vagas" (inicial maiúscula) conforme
     * o esquema de dados definitivo.
     *
     * @var string
     */
    protected $table = 'vagas';

    /**
     * Chave primária do modelo.
     *
     * A coluna id_vaga substitui a coluna id original.
     *
     * @var string
     */
    protected $primaryKey = 'id_vaga';

    /**
     * Valores padrão para os atributos.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'ATIVA',
    ];

    protected $fillable = [
        'id_instituicao',
        'status',
        'aluno_nascimento_mes',
        'aluno_nascimento_ano',
        'necessidades_descricao',
        'descricao',
        'carga_horaria_semanal',
        'regime_contratacao',
        'valor_remuneracao',
        'remuneracao',  // Alias para valor_remuneracao
        'tipo_remuneracao',
        'tipo',
        'modalidade',
        'titulo',  // Campo usado nos testes
        'titulo_vaga',
        'cidade',
        'estado',
    ];

    /**
     * Casts dos atributos.
     */
    protected $casts = [
        'data_criacao' => 'datetime',
        'remuneracao' => 'float',
        'valor_remuneracao' => 'float',
    ];

    /**
     * Atributos adicionais a serem incluídos na serialização do modelo.
     */
    protected $appends = [
        'id', // Inclui o accessor getIdAttribute() na serialização JSON
    ];

    /**
     * Scope para filtrar vagas ativas.
     */
    public function scopeAtivas($query)
    {
        return $query->where('status', 'ATIVA');
    }

    /**
     * Deficiências associadas à vaga. As deficiências foram normalizadas para
     * uma tabela separada com relação muitos-para-muitos.
     */
    public function deficiencias()
    {
        return $this->belongsToMany(Deficiencia::class, 'vagas_deficiencias', 'id_vaga', 'id_deficiencia');
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

    /**
     * Acessor para o campo titulo.
     * Retorna titulo_vaga se titulo não estiver definido.
     */
    public function getTituloAttribute($value)
    {
        // Se titulo estiver vazio, retorna titulo_vaga
        return $value ?: ($this->attributes['titulo_vaga'] ?? null);
    }

    /**
     * Mutator para o campo titulo.
     * Atualiza tanto titulo quanto titulo_vaga.
     */
    public function setTituloAttribute($value)
    {
        $this->attributes['titulo'] = $value;
        // Também atualizar titulo_vaga para compatibilidade
        if (!isset($this->attributes['titulo_vaga']) || empty($this->attributes['titulo_vaga'])) {
            $this->attributes['titulo_vaga'] = $value;
        }
    }

    /**
     * Accessor para o campo remuneracao.
     * Retorna valor_remuneracao se remuneracao não estiver definido.
     */
    public function getRemuneracaoAttribute($value)
    {
        // Se remuneracao estiver vazio, retorna valor_remuneracao
        return $value !== null ? $value : ($this->attributes['valor_remuneracao'] ?? null);
    }

    /**
     * Mutator para o campo remuneracao.
     * Atualiza tanto remuneracao quanto valor_remuneracao para compatibilidade.
     */
    public function setRemuneracaoAttribute($value)
    {
        $this->attributes['remuneracao'] = $value;
        // Também atualizar valor_remuneracao para compatibilidade
        if (!isset($this->attributes['valor_remuneracao'])) {
            $this->attributes['valor_remuneracao'] = $value;
        }
    }
}