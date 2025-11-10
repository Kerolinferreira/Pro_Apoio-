<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Instituição.
 *
 * Armazena informações sobre instituições de ensino e mantém o
 * relacionamento com o usuário e as vagas cadastradas.
 *
 * Usa SoftDeletes para manter consistência com o modelo User e preservar
 * dados relacionados quando o usuário é desativado.
 */
class Instituicao extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela associada ao modelo.
     *
     * Conforme o esquema, a tabela de instituições passou a se chamar
     * "Instituicoes" (inicial maiúscula) para respeitar a nomenclatura
     * definida nos arquivos CSV.
     *
     * @var string
     */
    protected $table = 'instituicoes';

    /**
     * Chave primária da tabela.
     *
     * O identificador único da instituição foi renomeado para
     * id_instituicao. Declarar explicitamente o nome da chave
     * primária garante que Eloquent utilize a coluna correta.
     *
     * @var string
     */
    protected $primaryKey = 'id_instituicao';

    protected $fillable = [
        // Identificador do usuário associado
        'id_usuario',
        // Endereço associado
        'id_endereco',
        // Dados de identificação
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'descricao',
        'codigo_inep',
        // Dados diversos
        'tipo_instituicao',
        'niveis_oferecidos',
        'nome_responsavel',
        'funcao_responsavel',
        'email_corporativo',
        'telefone_fixo',
        'celular_corporativo',
        // Arquivo de logo
        'logo_url',
    ];

    /**
     * Casts dos atributos.
     */
    protected $casts = [
        'niveis_oferecidos' => 'array',
    ];

    public function user()
    {
        // O campo de chave estrangeira foi renomeado para id_usuario
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Relacionamento com o endereço da instituição.
     */
    public function endereco()
    {
        return $this->belongsTo(Endereco::class, 'id_endereco');
    }

    public function vagas()
    {
        return $this->hasMany(Vaga::class, 'id_instituicao');
    }

    /**
     * Acessor para compatibilizar a propriedade id.
     *
     * Permite que chamadas ao atributo id retornem o valor de
     * id_instituicao, mantendo compatibilidade com código legado.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}