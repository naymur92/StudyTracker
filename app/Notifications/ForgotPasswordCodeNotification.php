<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPasswordCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $code
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
        return (new MailMessage)
            ->subject('StudyTracker Password Reset Code')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We received a request to reset your StudyTracker password.')
            ->line('Your verification code is: ' . $this->code)
            ->line('This code expires in 30 minutes and can be used only once.')
            ->line('If you did not request this, you can ignore this email safely.');
    }
}
