<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * @var array<int, string>
     */
    public array $months;

    public string $fileName;

    private string $csvData;

    /**
     * @param array<int, string> $months
     */
    public function __construct(User $user, array $months, string $fileName, string $csvData)
    {
        $this->user = $user;
        $this->months = $months;
        $this->fileName = $fileName;
        $this->csvData = $csvData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your StudyTracker Report',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.study-report',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->csvData, $this->fileName)
                ->withMime('text/csv'),
        ];
    }
}
