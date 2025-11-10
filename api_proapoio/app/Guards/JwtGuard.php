<?php

namespace App\Guards;

use App\Helpers\JwtHelper;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

/**
 * Guard customizado para autenticação JWT.
 * Integra o JwtHelper com o sistema de autenticação do Laravel.
 */
class JwtGuard implements Guard
{
    protected $request;
    protected $provider;
    protected $user;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Determina se o usuário atual está autenticado.
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Determina se o usuário atual é um convidado.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Retorna o usuário autenticado ou null.
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->request->bearerToken();

        if (!$token) {
            return null;
        }

        try {
            $payload = JwtHelper::decodeToken($token);

            if (!is_array($payload) || !isset($payload['sub'])) {
                return null;
            }

            $this->user = $this->provider->retrieveById($payload['sub']);

            return $this->user;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retorna o ID do usuário autenticado ou null.
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $user->getAuthIdentifier();
        }

        return null;
    }

    /**
     * Valida as credenciais do usuário.
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['email']) || empty($credentials['senha_hash'])) {
            return false;
        }

        $user = $this->provider->retrieveByCredentials($credentials);

        if (!$user) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Verifica se o usuário possui as credenciais fornecidas.
     */
    public function hasUser(): bool
    {
        return !is_null($this->user);
    }

    /**
     * Define o usuário autenticado.
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }
}
