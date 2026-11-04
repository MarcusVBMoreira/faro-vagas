<?php

namespace App\Notifications;

use App\Services\Auth\EmailCodeService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailCodeNotification extends Notification
{
    public function __construct(public string $code, public string $purpose)
    {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isLogin = $this->purpose === EmailCodeService::LOGIN;

        return (new MailMessage)
            ->subject($isLogin ? __('Seu código de acesso ao Faro: :code', ['code' => $this->code]) : __('Confirme seu e-mail no Faro: :code', ['code' => $this->code]))
            ->greeting(__('Olá, :name!', ['name' => strtok($notifiable->name, ' ')]))
            ->line($isLogin ? __('Use este código para concluir o login:') : __('Use este código para confirmar seu e-mail e ativar sua conta:'))
            ->line('# '.$this->code)
            ->line(__('O código vale por :minutes minutos.', ['minutes' => EmailCodeService::TTL_MINUTES]))
            ->line(__('Se não foi você, ignore este e-mail. Ninguém consegue entrar na sua conta sem este código.'));
    }
}
