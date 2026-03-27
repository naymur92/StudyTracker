<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\StudyTracker\BuildDailyAgendaService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DashboardApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(
        private BuildDailyAgendaService $agendaService
    ) {}

    /**
     * GET /api/study/dashboard?date=YYYY-MM-DD
     * Returns the daily agenda and summary stats for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $date   = $request->get('date', today()->toDateString());

        $agenda = $this->agendaService->execute($userId, $date);

        $stats = [
            'total_topics'    => Topic::where('user_id', $userId)->count(),
            'active_topics'   => Topic::where('user_id', $userId)->where('status', 'active')->count(),
            'today_pending'   => StudyTask::where('user_id', $userId)
                ->where('scheduled_date', today())
                ->where('status', 'pending')
                ->count(),
            'overdue'         => StudyTask::where('user_id', $userId)
                ->where('scheduled_date', '<', today())
                ->whereIn('status', ['pending', 'missed'])
                ->count(),
            'completed_today' => StudyTask::where('user_id', $userId)
                ->whereDate('completed_at', today())
                ->where('status', 'completed')
                ->count(),
            'streak'          => $this->calculateStreak($userId),
        ];

        return $this->jsonResponse(
            flag: true,
            message: 'Dashboard fetched successfully.',
            data: [
                'stats'  => $stats,
                'agenda' => $agenda,
            ],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * GET /api/study/calendar?year=2026&month=3
     */
    public function calendar(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $year   = (int) $request->get('year', now()->year);
        $month  = (int) $request->get('month', now()->month);

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $tasks = StudyTask::where('user_id', $userId)
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('scheduled_date, status, count(*) as count')
            ->groupBy('scheduled_date', 'status')
            ->get();

        $calendar = [];
        foreach ($tasks as $row) {
            $d = $row->scheduled_date->toDateString();
            if (! isset($calendar[$d])) {
                $calendar[$d] = ['total' => 0, 'completed' => 0, 'pending' => 0, 'overdue' => 0];
            }
            $calendar[$d]['total'] += $row->count;
            if ($row->status === 'completed') {
                $calendar[$d]['completed'] += $row->count;
            } elseif ($row->status === 'pending') {
                $calendar[$d]['pending'] += $row->count;
            }
        }

        return $this->jsonResponse(
            flag: true,
            message: 'Calendar fetched successfully.',
            data: [
                'year'     => $year,
                'month'    => $month,
                'calendar' => $calendar,
            ],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function calculateStreak(int $userId): int
    {
        $dates = StudyTask::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->selectRaw('DATE(completed_at) as d')
            ->groupBy('d')
            ->orderByDesc('d')
            ->limit(365)
            ->pluck('d')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->startOfDay());

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $expected = today();

        foreach ($dates as $date) {
            if ($date->equalTo($expected)) {
                $streak++;
                $expected = $expected->copy()->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
