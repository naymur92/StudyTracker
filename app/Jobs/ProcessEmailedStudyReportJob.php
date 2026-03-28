<?php

namespace App\Jobs;

use App\Mail\StudyReportMail;
use App\Models\EmailedStudyReport;
use App\Services\StudyTracker\BuildStudyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessEmailedStudyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $emailedStudyReportId;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public function __construct(int $emailedStudyReportId)
    {
        $this->emailedStudyReportId = $emailedStudyReportId;
        $this->onQueue('emails');
    }

    public function handle(BuildStudyReportService $reportService): void
    {
        $record = EmailedStudyReport::query()->with('user')->find($this->emailedStudyReportId);
        if (! $record || ! $record->user) {
            return;
        }

        $record->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $months = is_array($record->months) ? $record->months : [];
            $reportData = $reportService->buildReportData((int) $record->user_id, $months);
            $csv = $reportService->toCsv($reportData);

            $fileName = sprintf(
                'study-report-%s_to_%s.csv',
                $months[0] ?? now()->format('Y-m'),
                $months[count($months) - 1] ?? now()->format('Y-m')
            );

            Mail::to($record->user->email)
                ->send(new StudyReportMail($record->user, $months, $fileName, $csv));

            $record->update([
                'status' => 'sent',
                'generated_file_name' => $fileName,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            $record->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }
    }
}
