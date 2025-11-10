<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Proposta;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificação de proposta recusada.
 *
 * Enviada ao usuário que iniciou a proposta quando a outra parte a recusa.
 */
class PropostaRecusadaNotification extends Notification
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
            ->subject('Atualização sobre sua Proposta - ProApoio')
            ->markdown('emails.proposta-recusada', [
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
            'mensagem'    => 'Sua proposta foi recusada',
            'id_proposta' => $this->proposta->id,
        ];
    }
}
