<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\TipoUsuario;

/**
 * Middleware para garantir que apenas instituições acessem rotas específicas.
 *
 * Este middleware implementa Defense in Depth, impedindo que candidatos
 * acessem endpoints de instituições, mesmo que passem pela autenticação.
 *
 * Resolve Falha #1: Falta de middleware específico por tipo de usuário
 * Resolve Falha #14: Frontend valida permissões apenas client-side
 */
class EnsureInstituicao
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

        // Verificar se é instituição (case-insensitive)
        if (strtoupper($user->tipo_usuario ?? '') !== TipoUsuario::INSTITUICAO->value) {
            return response()->json([
                'message' => 'Acesso negado.'
            ], 403);
        }

        // Verificar se o perfil de instituição existe
        if (!$user->instituicao) {
            return response()->json([
                'message' => 'Perfil de instituição não encontrado.'
            ], 404);
        }

        return $next($request);
    }
}
