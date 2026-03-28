<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $userId;

    private string $token;

    public function __construct(int $userId, string $token)
    {
        $this->userId = $userId;
        $this->token = $token;
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $user->notify(new VerifyEmailNotification($this->token));
    }
}
