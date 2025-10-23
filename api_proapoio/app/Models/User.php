<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// A autenticação não utiliza mais Sanctum. Tokens JWT são gerados
// manualmente via helper. Remove-se a trait HasApiTokens.
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de usuário do sistema.
 *
 * Representa tanto candidatos quanto instituições, diferenciados pelo
 * campo user_type. Utiliza as traits HasApiTokens para autenticação via
 * Sanctum, Notifiable para notificações e HasFactory para factories.
 */
class User extends Authenticatable
{
    use Notifiable, HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * O esquema de dados define a tabela como "Usuarios" (com inicial
     * maiúscula) para diferenciar a nomenclatura legada. Definir
     * explicitamente a propriedade $table garante que o Eloquent
     * utilize o nome correto.
     *
     * @var string
     */
    protected $table = 'Usuarios';

    /**
     * Chave primária do modelo.
     *
     * A coluna de identificação dos usuários foi renomeada para
     * id_usuario de acordo com o esquema de dados. Configurar
     * $primaryKey garante que consultas e operações de Eloquent
     * utilizem a coluna correta.
     *
     * @var string
     */
    protected $primaryKey = 'id_usuario';

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * @var array<int,string>
     */
    /**
     * Os atributos que podem ser preenchidos em massa.
     * Ajustados para refletir os nomes de coluna definidos no esquema
     * (senha_hash, tipo_usuario, termos_aceite, data_termos_aceite).
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha_hash',
        'tipo_usuario',
        'termos_aceite',
        'data_termos_aceite',
    ];

    /**
     * Atributos ocultados na serialização.
     *
     * @var array<int,string>
     */
    /**
     * Atributos ocultados na serialização. O campo de senha passou a se chamar
     * senha_hash.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'senha_hash',
        'remember_token',
    ];

    /**
     * Acessor para compatibilidade com a propriedade id.
     *
     * Após a renomeação da coluna de chave primária para id_usuario,
     * muitos trechos de código ainda acessam $user->id. Este acessor
     * devolve o valor de id_usuario quando o atributo id é lido,
     * mantendo compatibilidade sem alterar todas as referências.
     *
     * @return mixed
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Define qual coluna será utilizada como senha para autenticação.
     *
     * @return string
     */
    /**
     * Define qual coluna será utilizada como senha para autenticação.
     * Como a coluna foi renomeada para senha_hash, retorna este atributo.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }

    /**
     * Relacionamento com o perfil de candidato.
     */
    public function candidato()
    {
        // A chave estrangeira em candidatos agora é id_usuario
        return $this->hasOne(Candidato::class, 'id_usuario');
    }

    /**
     * Relacionamento com o perfil de instituição.
     */
    public function instituicao()
    {
        // A chave estrangeira em instituicoes agora é id_usuario
        return $this->hasOne(Instituicao::class, 'id_usuario');
    }
}