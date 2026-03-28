<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\DownloadStudyReportRequest;
use App\Http\Requests\StudyTracker\QueueEmailedReportRequest;
use App\Jobs\ProcessEmailedStudyReportJob;
use App\Models\EmailedStudyReport;
use App\Services\StudyTracker\BuildStudyReportService;
use App\Traits\CustomResponseTrait;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportApiController extends Controller
{
    use CustomResponseTrait;

    private BuildStudyReportService $reportService;

    public function __construct(BuildStudyReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function download(DownloadStudyReportRequest $request)
    {
        $user = $request->user();
        $months = $request->months();

        $reportData = $this->reportService->buildReportData((int) $user->id, $months);
        $csv = $this->reportService->toCsv($reportData);

        $fileName = sprintf(
            'study-report-%s_to_%s.csv',
            $months[0],
            $months[count($months) - 1]
        );

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function queueEmail(QueueEmailedReportRequest $request)
    {
        $user = $request->user();
        $months = $request->input('months', []);

        $monthlyRequestCount = EmailedStudyReport::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($monthlyRequestCount >= 2) {
            return $this->jsonResponse(
                false,
                'Monthly emailed report limit reached (2 times). Please try again next month.',
                [],
                HttpResponse::HTTP_TOO_MANY_REQUESTS
            );
        }

        $record = EmailedStudyReport::create([
            'user_id' => $user->id,
            'months' => $months,
            'status' => 'queued',
        ]);

        ProcessEmailedStudyReportJob::dispatch($record->id);

        return $this->jsonResponse(
            true,
            'Report email request accepted and queued.',
            [
                'remaining_requests_this_month' => max(0, 2 - ($monthlyRequestCount + 1)),
            ],
            HttpResponse::HTTP_ACCEPTED
        );
    }
}
