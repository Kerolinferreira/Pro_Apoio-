<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Resposta JSON de sucesso. */
    protected function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    /** Resposta JSON de erro padronizada. */
    protected function error(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['message' => $message], $extra), $status);
    }

    /** Resposta 403. */
    protected function forbidden(string $message = 'Acesso negado.'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /** Resposta 404. */
    protected function notFound(string $message = 'Recurso não encontrado.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /** Resposta 422. */
    protected function unprocessable(string $message = 'Dados inválidos.', array $errors = []): JsonResponse
    {
        return $this->error($message, 422, ['errors' => $errors]);
    }

    /**
     * Empacota paginação no padrão JSON do app.
     * Ex.: return $this->paginated($paginator, fn($item) => [...]);
     */
    protected function paginated(LengthAwarePaginator $paginator, callable $map = null): JsonResponse
    {
        $items = $paginator->getCollection();
        if ($map) {
            $items = $items->map($map);
        }

        return response()->json([
            'data' => $items->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /** Extrai per_page seguro de 1..100. */
    protected function safePerPage(Request $request, int $default = 10): int
    {
        $perPage = (int) $request->integer('per_page', $default);
        return ($perPage > 0 && $perPage <= 100) ? $perPage : $default;
    }
}
