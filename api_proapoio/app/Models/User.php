<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject; // Adicionado para compatibilidade com JWT
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Notifications\ResetPasswordNotification;

/**
 * Modelo de usuário do sistema.
 *
 * Representa tanto candidatos quanto instituições, diferenciados pelo
 * campo tipo_usuario.
 *
 * Usa SoftDeletes para preservar dados relacionados e permitir auditoria.
 */
class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasFactory, SoftDeletes;

    /**
     * Nome da tabela associada ao modelo.
     *
     * O esquema de dados define a tabela como "Usuarios" (com inicial
     * maiúscula).
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Chave primária do modelo.
     *
     * A coluna de identificação dos usuários foi renomeada para 'id_usuario'.
     *
     * @var string
     */
    protected $primaryKey = 'id_usuario';

    /**
     * Os atributos que podem ser preenchidos em massa.
     * Ajustados para refletir os nomes de coluna definidos no esquema:
     * senha_hash, tipo_usuario, termos_aceite, data_termos_aceite.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',  // Campo senha para testes
        'senha_hash',
        'tipo_usuario',
        'termos_aceite',
        'data_termos_aceite',
    ];

    /**
     * Atributos ocultados na serialização. O campo de senha passou a se chamar
     * senha_hash.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',  // Campo senha para testes
        'senha_hash',
        'remember_token',
    ];

    /**
     * Acessor para compatibilidade com a propriedade id.
     * Devolve o valor de id_usuario quando o atributo id é lido.
     *
     * @return mixed
     */
    public function getIdAttribute(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Define qual coluna será utilizada como senha para autenticação (JWT).
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->senha_hash;
    }

    // Métodos necessários para JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Mutator para o campo senha.
     * Criptografa automaticamente a senha antes de armazenar.
     *
     * @param string $value
     * @return void
     */
    public function setSenhaAttribute(string $value): void
    {
        $this->attributes['senha'] = bcrypt($value);
    }

    /**
     * Relacionamento com o perfil de candidato.
     * Chave estrangeira: id_usuario. Chave local: id_usuario.
     */
    public function candidato(): HasOne
    {
        // ✅ CORREÇÃO: Definir explicitamente a chave local (id_usuario)
        return $this->hasOne(Candidato::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Relacionamento com o perfil de instituição.
     * Chave estrangeira: id_usuario. Chave local: id_usuario.
     */
    public function instituicao(): HasOne
    {
        // ✅ CORREÇÃO: Definir explicitamente a chave local (id_usuario)
        return $this->hasOne(Instituicao::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Envia a notificação de reset de senha.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Acessor para nome_completo.
     * Retorna o nome do usuário.
     *
     * @return string
     */
    public function getNomeCompletoAttribute(): string
    {
        return $this->nome;
    }
}