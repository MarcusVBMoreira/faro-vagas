<?php

namespace App\Notifications;

use App\Models\JobMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * E-mail diário com as melhores vagas novas para a pessoa (no idioma dela).
 */
class DailyDigestNotification extends Notification
{
    use Queueable;

    /** @param Collection<int, JobMatch> $matches */
    public function __construct(public Collection $matches)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $first = strtok((string) $notifiable->name, ' ');
        $mail = (new MailMessage)
            ->subject(trans_choice('Faro: :count vaga nova para você|Faro: :count vagas novas para você', $this->matches->count(), ['count' => $this->matches->count()]))
            ->greeting(__('Olá, :name!', ['name' => $first]))
            ->line(__('Estas são as vagas novas que mais combinam com o seu perfil:'));

        foreach ($this->matches as $match) {
            $job = $match->jobPosting;
            $where = collect([$job->company, $job->locationLabel(), $job->modality?->label()])->filter()->implode(' · ');
            $mail->line("**{$match->score}% · {$job->title}**  \n{$where}  \n[".__('Ver vaga').']('.route('jobs.show', $job).')');
        }

        $unsubscribe = URL::signedRoute('digest.unsubscribe', ['user' => $notifiable->id]);

        return $mail
            ->action(__('Ver todas as vagas para você'), route('matches.index'))
            ->line(__('Você recebe este e-mail porque ativou o resumo diário no Faro.'))
            ->line('['.__('Não quero mais receber este e-mail').']('.$unsubscribe.')');
    }
}
