<?php

namespace App\Observers;

use App\Models\Proposta;
use App\Notifications\PropostaAceitaNotification;
use App\Enums\TipoUsuario;

/**
 * Observer para o modelo Proposta.
 *
 * Este observador intercepta eventos de criação e atualização de propostas
 * para registrar notificações no banco e disparar e-mails transacionais. A
 * implementação real do envio de e-mails e criação das notificações ainda
 * deve ser feita conforme a infraestrutura de notificação do projeto.
 */
class PropostaObserver
{
    /**
     * Ação ao criar uma nova proposta.
     *
     * Notificações de nova proposta são gerenciadas pelo PropostaController
     * para ter controle explícito do fluxo e evitar duplicação.
     *
     * @param Proposta $proposta
     */
    public function created(Proposta $proposta)
    {
        // Notificações são disparadas no PropostaController::store()
        // para evitar notificações duplicadas
    }

    /**
     * Ação ao atualizar uma proposta.
     *
     * @param Proposta $proposta
     */
    public function updated(Proposta $proposta)
    {
        // Ao aceitar uma proposta, notifique o iniciador
        if ($proposta->isDirty('status') && $proposta->status === 'ACEITA') {
            if ($proposta->iniciador === TipoUsuario::CANDIDATO->value) {
                // iniciador é candidato, notificar candidato
                $destinatario = $proposta->candidato->user;
            } else {
                // iniciador é instituição, notificar instituição
                $destinatario = $proposta->vaga->instituicao->user;
            }
            if ($destinatario) {
                $destinatario->notify(new PropostaAceitaNotification($proposta));
            }
        }
    }
}