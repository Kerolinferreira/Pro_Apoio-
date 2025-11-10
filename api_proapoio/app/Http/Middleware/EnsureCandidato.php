<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\TipoUsuario;

/**
 * Middleware para garantir que apenas candidatos acessem rotas específicas.
 *
 * Este middleware implementa Defense in Depth, impedindo que instituições
 * acessem endpoints de candidatos, mesmo que passem pela autenticação.
 *
 * Resolve Falha #1: Falta de middleware específico por tipo de usuário
 * Resolve Falha #14: Frontend valida permissões apenas client-side
 */
class EnsureCandidato
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificar se usuário está autenticado
        if (!$user) {
            return response()->json([
                'message' => 'Não autenticado.'
            ], 401);
        }

        // Verificar se é candidato (case-insensitive)
        if (strtoupper($user->tipo_usuario ?? '') !== TipoUsuario::CANDIDATO) {
            return response()->json([
                'message' => 'Apenas candidatos.'
            ], 403);
        }

        // Verificar se o perfil de candidato existe
        if (!$user->candidato) {
            return response()->json([
                'message' => 'Perfil de candidato não encontrado.'
            ], 404);
        }

        return $next($request);
    }
}
