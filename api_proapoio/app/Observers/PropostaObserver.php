<?php

namespace App\Observers;

use App\Models\Proposta;
use App\Notifications\NovaPropostaNotification;
use App\Notifications\PropostaAceitaNotification;

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
     * @param Proposta $proposta
     */
    public function created(Proposta $proposta)
    {
        // Determina quem deve ser notificado: se iniciador é candidato,
        // notificar a instituição; caso contrário, notificar o candidato
        if ($proposta->iniciador === 'CANDIDATO') {
            $destinatario = $proposta->vaga->instituicao->user;
        } else {
            $destinatario = $proposta->candidato->user;
        }
        if ($destinatario) {
            $destinatario->notify(new NovaPropostaNotification($proposta));
        }
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
            if ($proposta->iniciador === 'CANDIDATO') {
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