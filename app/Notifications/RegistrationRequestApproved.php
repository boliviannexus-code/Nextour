<?php

namespace App\Notifications;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationRequestApproved extends Notification
{
    use Queueable;

    public function __construct(
        private readonly RegistrationRequest $registrationRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Solicitud aprobada')
            ->greeting('Hola '.$notifiable->name)
            ->line('Su solicitud ha sido aprobada.')
            ->line('Ya puede acceder a la plataforma.')
            ->action('Ingresar', route('login'));
    }
}
