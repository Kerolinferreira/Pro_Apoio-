<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Vaga;

/**
 * Notificação de exclusão de vaga.
 *
 * Dispara um aviso aos candidatos que possuem propostas para uma vaga
 * que foi excluída pela instituição. Envia notificação via base de dados
 * e opcionalmente por e-mail.
 */
class VagaExcluidaNotification extends Notification
{
    use Queueable;

    protected array $vagaData;

    /**
     * Recebe array com dados da vaga pois a vaga será excluída.
     *
     * @param array $vagaData Dados da vaga (id_vaga, titulo_vaga, etc)
     */
    public function __construct(array $vagaData)
    {
        $this->vagaData = $vagaData;
    }

    /**
     * Canais de notificação utilizados.
     *
     * @param mixed $notifiable
     * @return array<int,string>
     */
    public function via($notifiable)
    {
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
        $tituloVaga = $this->vagaData['titulo_vaga'] ?? $this->vagaData['titulo'] ?? 'Vaga';
        $nomeInstituicao = $this->vagaData['nome_instituicao'] ?? 'Instituição';

        return (new MailMessage())
            ->subject('Vaga Excluída - ProApoio')
            ->line("Informamos que a vaga \"{$tituloVaga}\" da instituição {$nomeInstituicao} foi removida.")
            ->line('Sua proposta para esta vaga não está mais ativa.')
            ->line('Agradecemos seu interesse e convidamos você a explorar outras oportunidades na plataforma.')
            ->action('Ver Outras Vagas', url('/vagas'))
            ->line('Equipe ProApoio');
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
            'tipo'           => 'vaga_excluida',
            'mensagem'       => 'A vaga "' . ($this->vagaData['titulo_vaga'] ?? $this->vagaData['titulo'] ?? 'Vaga') . '" foi excluída',
            'id_vaga'        => $this->vagaData['id_vaga'] ?? null,
            'titulo_vaga'    => $this->vagaData['titulo_vaga'] ?? $this->vagaData['titulo'] ?? null,
            'nome_instituicao' => $this->vagaData['nome_instituicao'] ?? null,
        ];
    }
}
