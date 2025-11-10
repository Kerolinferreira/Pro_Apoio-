<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Proposta;

/**
 * Notificação de nova proposta.
 *
 * Dispara um aviso ao destinatário (instituição ou candidato) quando uma
 * nova proposta é criada. Envia uma notificação via base de dados e
 * opcionalmente por e‑mail.
 */
class NovaPropostaNotification extends Notification
{
    use Queueable;

    protected Proposta $proposta;

    public function __construct(Proposta $proposta)
    {
        $this->proposta = $proposta;
    }

    /**
     * Canais de notificação utilizados.
     *
     * @param mixed $notifiable
     * @return array<int,string>
     */
    public function via($notifiable)
    {
        // Envia a notificação via banco de dados e e‑mail. O canal de e‑mail
        // permite disparar mensagens transacionais simples para fins de
        // confirmação. O envio de e‑mails pode ser configurado conforme a
        // infraestrutura disponível.
        return ['database', 'mail'];
    }

    /**
     * Representação da notificação para o driver 'mail'.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Carrega relacionamentos necessários para o template
        $this->proposta->load(['candidato.user', 'vaga.instituicao.user']);

        return (new MailMessage())
            ->subject('Nova Proposta Recebida - ProApoio')
            ->markdown('emails.nova-proposta', [
                'proposta' => $this->proposta,
                'destinatario' => $notifiable->nome_completo ?? $notifiable->nome,
            ]);
    }

    /**
     * Representação em array da notificação para o driver 'database'.
     *
     * @param mixed $notifiable
     * @return array<string,mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'mensagem'      => 'Nova proposta recebida',
            'id_proposta'   => $this->proposta->id,
            'id_vaga'       => $this->proposta->id_vaga,
            'id_candidato'  => $this->proposta->id_candidato,
        ];
    }
}