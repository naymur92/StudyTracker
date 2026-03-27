<?php

namespace App\Http\Controllers;

use App\Models\PracticeLog;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStudyTrackerController extends Controller
{
    /**
     * GET /admin/study-tracker
     * App-wide overview for the admin.
     */
    public function overview(Request $request)
    {
        $stats = [
            'total_users_with_topics' => Topic::distinct('user_id')->count('user_id'),
            'total_topics'            => Topic::count(),
            'active_topics'           => Topic::where('status', 'active')->count(),
            'total_tasks'             => StudyTask::count(),
            'completed_tasks'         => StudyTask::where('status', 'completed')->count(),
            'pending_tasks'           => StudyTask::where('status', 'pending')->count(),
            'overdue_tasks'           => StudyTask::where('scheduled_date', '<', today())
                ->whereIn('status', ['pending', 'missed'])
                ->count(),
            'total_practice_logs'     => PracticeLog::count(),
            'completed_today'         => StudyTask::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'tasks_scheduled_today'   => StudyTask::where('scheduled_date', today())->count(),
        ];

        // Completion rate
        $stats['completion_rate'] = $stats['total_tasks'] > 0
            ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1)
            : 0;

        // Daily completions for the last 14 days
        $dailyCompletions = StudyTask::where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(completed_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $last14Days = collect();
        for ($i = 13; $i >= 0; $i--) {
            $d = today()->subDays($i)->toDateString();
            $last14Days->put($d, $dailyCompletions->get($d, 0));
        }

        // Per-user summary (top 20 by topic count)
        $userSummaries = User::withCount([
            'topics',
            'topics as active_topics_count' => fn($q) => $q->where('status', 'active'),
            'studyTasks as completed_tasks' => fn($q) => $q->where('status', 'completed'),
            'studyTasks as pending_tasks' => fn($q) => $q->whereIn('status', ['pending', 'missed']),
            'studyTasks as overdue_tasks' => fn($q) => $q->where('scheduled_date', '<', today())->whereIn('status', ['pending', 'missed']),
        ])
            ->having('topics_count', '>', 0)
            ->orderByDesc('topics_count')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return view('pages.admin-study-tracker.overview', compact(
            'stats',
            'last14Days',
            'userSummaries',
        ));
    }

    /**
     * GET /admin/study-tracker/reports
     * Aggregated reports.
     */
    public function reports(Request $request)
    {
        $dateFrom = $request->get('date_from', today()->subDays(29)->toDateString());
        $dateTo   = $request->get('date_to', today()->toDateString());

        // Completion trend per day in range
        $completionTrend = StudyTask::where('status', 'completed')
            ->whereDate('completed_at', '>=', $dateFrom)
            ->whereDate('completed_at', '<=', $dateTo)
            ->selectRaw('DATE(completed_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Fill gaps
        $trendDays = collect();
        $cursor    = Carbon::parse($dateFrom);
        $end       = Carbon::parse($dateTo);
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();
            $trendDays->put($d, $completionTrend->get($d, 0));
            $cursor->addDay();
        }

        // Task type breakdown
        $taskTypeBreakdown = StudyTask::where('status', 'completed')
            ->selectRaw('task_type, revision_no, count(*) as count')
            ->groupBy('task_type', 'revision_no')
            ->get();

        // Top topics by completion (revision count)
        $topTopics = Topic::withCount([
            'studyTasks as completed_count' => fn($q) => $q->where('status', 'completed'),
            'studyTasks as total_count',
        ])
            ->having('total_count', '>', 0)
            ->orderByDesc('completed_count')
            ->limit(15)
            ->get(['id', 'user_id', 'title', 'difficulty', 'status']);

        $topTopics->load('user:id,name');

        // Practice log breakdown by type
        $practiceTypeBreakdown = PracticeLog::selectRaw('practice_type, count(*) as count, sum(duration_minutes) as total_minutes')
            ->groupBy('practice_type')
            ->get();

        // Most active users (by completions in range)
        $activeUsers = StudyTask::where('status', 'completed')
            ->whereDate('completed_at', '>=', $dateFrom)
            ->whereDate('completed_at', '<=', $dateTo)
            ->selectRaw('user_id, count(*) as completions')
            ->groupBy('user_id')
            ->orderByDesc('completions')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        return view('pages.admin-study-tracker.reports', compact(
            'trendDays',
            'taskTypeBreakdown',
            'topTopics',
            'practiceTypeBreakdown',
            'activeUsers',
            'dateFrom',
            'dateTo',
        ));
    }

    /**
     * GET /admin/study-tracker/users/{user}
     * Per-user deep report.
     */
    public function userReport(Request $request, User $user)
    {
        if ((int) $user->type !== 3) {
            abort(404, 'Study tracker report is available only for user type 3.');
        }

        $topics = Topic::with('category')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $taskStats = [
            'total'     => StudyTask::where('user_id', $user->id)->count(),
            'completed' => StudyTask::where('user_id', $user->id)->where('status', 'completed')->count(),
            'pending'   => StudyTask::where('user_id', $user->id)->where('status', 'pending')->count(),
            'skipped'   => StudyTask::where('user_id', $user->id)->where('status', 'skipped')->count(),
            'overdue'   => StudyTask::where('user_id', $user->id)
                ->where('scheduled_date', '<', today())
                ->whereIn('status', ['pending', 'missed'])
                ->count(),
        ];

        $taskStats['completion_rate'] = $taskStats['total'] > 0
            ? round(($taskStats['completed'] / $taskStats['total']) * 100, 1)
            : 0;

        $recentPracticeLogs = PracticeLog::with('topic')
            ->where('user_id', $user->id)
            ->orderByDesc('practiced_on')
            ->limit(20)
            ->get();

        $upcomingTasks = StudyTask::with('topic')
            ->where('user_id', $user->id)
            ->where('scheduled_date', '>=', today())
            ->whereIn('status', ['pending'])
            ->orderBy('scheduled_date')
            ->limit(15)
            ->get();

        return view('pages.admin-study-tracker.user-report', compact(
            'user',
            'topics',
            'taskStats',
            'recentPracticeLogs',
            'upcomingTasks',
        ));
    }

    /**
     * GET /admin/study-tracker/users-report
     * Complete list of all users with study tracker stats.
     */
    public function usersReport(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'topics_count');
        $order = $request->get('order', 'desc');

        $query = User::where('type', 3)->withCount([
            'topics',
            'topics as active_topics_count' => fn($q) => $q->where('status', 'active'),
            'studyTasks as completed_tasks' => fn($q) => $q->where('status', 'completed'),
            'studyTasks as pending_tasks' => fn($q) => $q->whereIn('status', ['pending', 'missed']),
            'studyTasks as overdue_tasks' => fn($q) => $q->where('scheduled_date', '<', today())->whereIn('status', ['pending', 'missed']),
            'practiceLogs as practice_logs_count',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $validSorts = ['topics_count', 'active_topics_count', 'name', 'created_at'];
        if (in_array($sort, $validSorts)) {
            $query->orderBy($sort, $order === 'asc' ? 'asc' : 'desc');
        }

        $users = $query->paginate(20)->withQueryString();

        return view('pages.admin-study-tracker.users-report', compact('users', 'search', 'sort', 'order'));
    }

    /**
     * GET /admin/study-tracker/topics-report
     * All topics with detailed stats.
     */
    public function topicsReport(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $difficulty = $request->get('difficulty', '');
        $sort = $request->get('sort', 'created_at');

        $query = Topic::with(['user:id,name,email', 'category:id,name'])
            ->withCount([
                'studyTasks',
                'studyTasks as completed_tasks' => fn($q) => $q->where('status', 'completed'),
                'studyTasks as pending_tasks' => fn($q) => $q->whereIn('status', ['pending', 'missed']),
                'practiceLogs',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        $validSorts = ['created_at', 'title', 'difficulty', 'study_tasks_count', 'completed_tasks'];
        if (in_array($sort, $validSorts)) {
            $query->orderBy($sort, 'desc');
        } else {
            $query->latest();
        }

        $topics = $query->paginate(25)->withQueryString();

        return view('pages.admin-study-tracker.topics-report', compact(
            'topics',
            'search',
            'status',
            'difficulty',
            'sort',
        ));
    }

    /**
     * GET /admin/study-tracker/categories-report
     * All categories with stats.
     */
    public function categoriesReport(Request $request)
    {
        $type = $request->get('type', 'all'); // user, system, all

        $query = Category::withCount([
            'topics',
            'topics as active_topics' => fn($q) => $q->where('status', 'active'),
        ]);

        if ($type === 'user') {
            $query->whereNotNull('user_id');
        } elseif ($type === 'system') {
            $query->whereNull('user_id');
        }

        $categories = $query->orderBy('name')->paginate(30)->withQueryString();

        // Load task counts via a single aggregated query
        $categoryIds = $categories->pluck('id');
        $taskCounts = StudyTask::join('topics', 'study_tasks.topic_id', '=', 'topics.id')
            ->whereIn('topics.category_id', $categoryIds)
            ->selectRaw('topics.category_id, count(*) as total_tasks, sum(case when study_tasks.status = ? then 1 else 0 end) as completed_tasks', ['completed'])
            ->groupBy('topics.category_id')
            ->get()
            ->keyBy('category_id');

        foreach ($categories as $cat) {
            $counts = $taskCounts->get($cat->id);
            $cat->total_tasks = $counts->total_tasks ?? 0;
            $cat->completed_tasks = $counts->completed_tasks ?? 0;
        }

        return view('pages.admin-study-tracker.categories-report', compact('categories', 'type'));
    }

    /**
     * GET /admin/study-tracker/tasks-report
     * All study tasks with filtering and management.
     */
    public function tasksReport(Request $request)
    {
        $status = $request->get('status', '');
        $taskType = $request->get('task_type', '');
        $userId = $request->get('user_id', '');
        $topicId = $request->get('topic_id', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $sort = $request->get('sort', 'scheduled_date');

        $query = StudyTask::with(['user:id,name,email', 'topic:id,title,user_id']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($taskType) {
            $query->where('task_type', $taskType);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        if ($dateFrom) {
            $query->where('scheduled_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('scheduled_date', '<=', $dateTo);
        }

        $validSorts = ['scheduled_date', 'created_at', 'status', 'task_type'];
        if (in_array($sort, $validSorts)) {
            $query->orderBy($sort, $sort === 'scheduled_date' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $tasks = $query->paginate(30)->withQueryString();

        $users = User::has('studyTasks')->get(['id', 'name']);
        $topics = Topic::has('studyTasks')->get(['id', 'title']);

        return view('pages.admin-study-tracker.tasks-report', compact(
            'tasks',
            'status',
            'taskType',
            'userId',
            'topicId',
            'dateFrom',
            'dateTo',
            'sort',
            'users',
            'topics',
        ));
    }
}
