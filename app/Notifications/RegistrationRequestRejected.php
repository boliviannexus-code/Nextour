<?php

namespace App\Notifications;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationRequestRejected extends Notification
{
    use Queueable;

    public function __construct(
        private readonly RegistrationRequest $registrationRequest,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Solicitud rechazada')
            ->greeting('Hola '.$notifiable->name)
            ->line('Su solicitud ha sido rechazada.')
            ->line('Motivo:')
            ->line($this->reason);
    }
}
