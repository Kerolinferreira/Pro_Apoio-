<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Candidato.
 *
 * Armazena as informações pessoais do candidato, como CPF, endereço,
 * escolaridade e experiências. Está relacionado ao modelo User.
 *
 * Usa SoftDeletes para manter consistência com o modelo User e preservar
 * dados relacionados quando o usuário é desativado.
 */
class Candidato extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela associada ao modelo.
     *
     * Conforme o esquema de dados, a tabela segue o padrão Laravel
     * em minúsculas: "candidatos".
     *
     * @var string
     */
    protected $table = 'candidatos';

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
        'data_nascimento',
        'genero',
        'foto_url',  // Campo legado
        'foto_perfil_url',  // Campo novo
        'link_perfil',
        'escolaridade',  // Campo usado nos testes
        'nivel_escolaridade',
        'curso_superior',
        'instituicao_ensino',
        'experiencia',
        'status',
    ];

    /**
     * Casts dos atributos.
     */
    protected $casts = [
        'data_nascimento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Atributos adicionais a serem incluídos na serialização do modelo.
     *
     * NOTA: O atributo 'experiencias' foi removido de $appends para evitar
     * N+1 queries. Use eager loading quando necessário:
     * Candidato::with('experienciasProfissionais', 'experienciasPessoais')->get()
     */
    protected $appends = [
        'id', // Inclui o accessor getIdAttribute() na serialização JSON
        // 'experiencias' - Removido para evitar N+1 queries
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
     * Deficiências associadas ao candidato.
     *
     * Relacionamento many-to-many com a tabela pivot candidato_deficiencia.
     */
    public function deficiencias()
    {
        return $this->belongsToMany(
            Deficiencia::class,
            'candidato_deficiencia',
            'id_candidato',
            'id_deficiencia'
        );
    }

    /**
     * Accessor para combinar experiências profissionais e pessoais em um único array.
     * Retorna todas as experiências (profissionais e pessoais) formatadas uniformemente.
     */
    public function getExperienciasAttribute()
    {
        $experiencias = [];

        // Adiciona experiências profissionais
        foreach ($this->experienciasProfissionais as $exp) {
            $experiencias[] = [
                'id' => $exp->id_experiencia_profissional,
                'tipo' => 'profissional',
                'titulo' => 'Experiência Profissional',
                'descricao' => $exp->descricao ?? '',
                'data_inicio' => null,
                'data_fim' => null,
                'idade_aluno' => $exp->idade_aluno,
                'tempo_experiencia' => $exp->tempo_experiencia,
                'interesse_mesma_deficiencia' => $exp->interesse_mesma_deficiencia,
                'deficiencias' => $exp->deficiencias ?? []
            ];
        }

        // Adiciona experiências pessoais
        foreach ($this->experienciasPessoais as $exp) {
            $experiencias[] = [
                'id' => $exp->id_experiencia_pessoal,
                'tipo' => 'pessoal',
                'titulo' => 'Experiência Pessoal',
                'descricao' => $exp->descricao ?? '',
                'data_inicio' => null,
                'data_fim' => null,
                'interesse_atuar' => $exp->interesse_atuar
            ];
        }

        return $experiencias;
    }

    /**
     * Mutator para CPF: remove formatação antes de armazenar.
     * Armazena apenas os dígitos numéricos.
     */
    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = preg_replace('/\D/', '', $value);
    }

    /**
     * Mutator para Telefone: remove formatação antes de armazenar.
     * Armazena apenas os dígitos numéricos.
     */
    public function setTelefoneAttribute($value)
    {
        $this->attributes['telefone'] = $value ? preg_replace('/\D/', '', $value) : null;
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