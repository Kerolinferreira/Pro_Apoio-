<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Models\User;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token de autenticação ausente'], 401);
        }

        try {
            $payload = JwtHelper::decodeToken($token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Token inválido ou expirado'], 401);
        }

        if (!is_array($payload) || !isset($payload['sub'])) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $user = User::find($payload['sub']);
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 401);
        }

        // Disponibiliza usuário e payload na request
        $request->attributes->set('jwt_payload', $payload);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
