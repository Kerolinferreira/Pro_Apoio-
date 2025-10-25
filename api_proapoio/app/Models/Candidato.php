<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Candidato.
 *
 * Armazena as informações pessoais do candidato, como CPF, endereço,
 * escolaridade e experiências. Está relacionado ao modelo User.
 */
class Candidato extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * Conforme o esquema de dados, a tabela foi renomeada para
     * "Candidatos" com inicial maiúscula.
     *
     * @var string
     */
    protected $table = 'Candidatos';

    /**
     * Nome da chave primária.
     * A coluna id_candidato substitui a coluna genérica id.
     *
     * @var string
     */
    protected $primaryKey = 'id_candidato';

    protected $fillable = [
        // Identificador do usuário associado
        'id_usuario',
        // Relacionamento com endereço
        'id_endereco',
        // Dados básicos do candidato
        'nome_completo',
        'cpf',
        'telefone',
        'link_perfil',
        'nivel_escolaridade',
        'curso_superior',
        'instituicao_ensino',
        'foto_url',
        'status',
    ];

    /**
     * Relacionamento com User.
     */
    public function user()
    {
        // O campo de chave estrangeira foi renomeado para id_usuario
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Relacionamento com o endereço do candidato.
     */
    public function endereco()
    {
        return $this->belongsTo(Endereco::class, 'id_endereco');
    }

    /**
     * Experiências profissionais do candidato.
     */
    public function experienciasProfissionais()
    {
        return $this->hasMany(ExperienciaProfissional::class, 'id_candidato');
    }

    /**
     * Experiências pessoais do candidato.
     */
    public function experienciasPessoais()
    {
        return $this->hasMany(ExperienciaPessoal::class, 'id_candidato');
    }

    /**
     * Acessor para compatibilizar a propriedade id com o novo nome da
     * chave primária.
     *
     * Permite que chamadas ao atributo id retornem o valor de
     * id_candidato, preservando compatibilidade com código legado.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}