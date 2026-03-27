<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $token
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('email.verify', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Verify Your StudyTracker Account')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thanks for registering. Please verify your email to activate your account.')
            ->action('Verify Email', $url)
            ->line('This link expires in 30 minutes.')
            ->line('If you did not create this account, you can safely ignore this email.');
    }
}
