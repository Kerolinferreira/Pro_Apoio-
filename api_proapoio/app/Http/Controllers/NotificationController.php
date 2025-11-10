<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * GET /notificacoes
     * Lista notificações do usuário (padrão: não lidas), com paginação.
     * Query:
     *  - status: unread|read|all (default: unread)
     *  - per_page: 1..100 (default: 10)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $status  = $request->input('status', 'unread');
        $perPage = $this->safePerPage($request, 10);

        $base = $user->notifications()->orderByDesc('created_at');

        if ($status === 'unread') {
            $base->whereNull('read_at');
        } elseif ($status === 'read') {
            $base->whereNotNull('read_at');
        } // 'all' não filtra

        $paginator = $base->paginate($perPage)->appends($request->query());

        // Retorna apenas campos úteis
        return $this->paginated($paginator, function (DatabaseNotification $n) {
            // Converter nome da classe para snake_case
            // Ex: NovaPropostaNotification → nova_proposta_notification
            $typeSnakeCase = \Illuminate\Support\Str::snake(class_basename($n->type));

            return [
                'id'         => $n->id,
                'type'       => $typeSnakeCase,
                'data'       => $n->data,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ];
        });
    }

    /**
     * POST /notificacoes/marcar-como-lidas
     * Body:
     *  - ids: string[] (opcional)
     *  - all: bool (opcional) -> se true, marca todas como lidas
     */
    public function markRead(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'ids' => 'sometimes|array',
            'ids.*' => 'string',
            'all' => 'sometimes|boolean',
        ]);

        if (!empty($data['all'])) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            return response()->json(['message' => 'Todas as notificações marcadas como lidas.']);
        }

        $ids = $data['ids'] ?? [];
        if (empty($ids)) {
            return response()->json(['message' => 'Nenhum ID informado.'], 422);
        }

        $user->unreadNotifications()
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificações marcadas como lidas.']);
    }
}
