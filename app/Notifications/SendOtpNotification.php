<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable;

    public function __construct(protected string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Login Anda')
            ->greeting("Halo {$notifiable->name},")
            ->line("Kode OTP Anda adalah: {$this->code}")
            ->line('Kode ini berlaku selama 10 menit.')
            ->line('Jangan bagikan kode ini ke siapa pun.');
    }
}