<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Endereço.
 *
 * Representa os dados de endereço utilizados tanto por candidatos quanto por
 * instituições. Normaliza informações de localização para evitar
 * duplicidade de colunas em outras tabelas.
 */
class Endereco extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     * Utiliza a convenção do esquema "Enderecos" com inicial maiúscula.
     *
     * @var string
     */
    protected $table = 'enderecos';

    /**
     * Chave primária.
     * A coluna id_endereco substitui a antiga coluna id.
     *
     * @var string
     */
    protected $primaryKey = 'id_endereco';

    protected $fillable = [
        'cep',
        'logradouro',
        'bairro',
        'cidade',
        'estado',
        'numero',
        'complemento',
        'ponto_referencia',
    ];

    public $timestamps = false;

    /**
     * Acessor de compatibilidade para a propriedade id.
     *
     * Muitos trechos de código ainda utilizam $endereco->id. Este
     * acessor direciona a chamada para a coluna id_endereco, mantendo
     * compatibilidade com o código anterior sem prejudicar a
     * nomenclatura do banco.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Candidatos associados a este endereço.
     */
    public function candidatos()
    {
        return $this->hasMany(Candidato::class, 'id_endereco');
    }

    /**
     * Instituições associadas a este endereço.
     */
    public function instituicoes()
    {
        return $this->hasMany(Instituicao::class, 'id_endereco');
    }
}