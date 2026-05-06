<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AriatyxResetPassword extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset Password - Ariatyx Gaming')
            ->greeting('Hello!')
            ->line('You requested a password reset for your Ariatyx Gaming account.')
            ->action('Reset Password', $url)
            ->line('This link will expire in 60 minutes.')
            ->line('If you did not request this, ignore this email.')
            ->salutation('— Ariatyx Gaming');
    }
}