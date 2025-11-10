<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Proposta;

// Necessário para construção de mensagens de e‑mail
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificação de proposta aceita.
 *
 * Enviada ao usuário que iniciou a proposta quando a outra parte a aceita.
 */
class PropostaAceitaNotification extends Notification
{
    use Queueable;

    protected Proposta $proposta;

    public function __construct(Proposta $proposta)
    {
        $this->proposta = $proposta;
    }

    /**
     * Canais utilizados.
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Representação para o canal de e‑mail.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Carrega relacionamentos necessários para o template
        $this->proposta->load(['candidato.user', 'vaga.instituicao.user']);

        return (new MailMessage())
            ->subject('🎉 Parabéns! Sua Proposta foi Aceita - ProApoio')
            ->markdown('emails.proposta-aceita', [
                'proposta' => $this->proposta,
                'destinatario' => $notifiable->nome_completo ?? $notifiable->nome,
            ]);
    }

    /**
     * Representação da notificação para o driver 'database'.
     */
    public function toArray($notifiable)
    {
        return [
            'mensagem'    => 'Sua proposta foi aceita',
            'id_proposta' => $this->proposta->id,
        ];
    }
}