<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ForgotPasswordCodeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendForgotPasswordCodeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $userId;

    private string $code;

    public function __construct(int $userId, string $code)
    {
        $this->userId = $userId;
        $this->code = $code;
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $user->notify(new ForgotPasswordCodeNotification($this->code));
    }
}
