<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\PracticeLog;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Models\User;
use App\Services\StudyTracker\MistakeService;
use App\Services\StudyTracker\StudyPreferences;
use App\Services\StudyTracker\WeeklyPlanService;
use App\Models\StudyBlock;
use App\Models\StudyWeek;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetDemoUser extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset (or create) the demo user with fresh sample data';

    private const DEMO_EMAIL = 'demo@studytracker.com';
    private const DEMO_NAME  = 'Demo User';

    /** Sample recall cards so the review page has real self-tests to show. */
    private const RECALL_CARDS = [
        'Closures & Scope' => [
            'questions' => [
                ['question' => 'What is a closure?', 'answer' => 'A function bundled with references to the variables of the scope it was created in.'],
                ['question' => 'Why does a loop with var print the same value in every callback?', 'answer' => 'var is function-scoped, so every callback closes over the same binding; let creates one binding per iteration.'],
                ['question' => 'Name one practical use of closures.', 'answer' => 'Private state, e.g. a counter factory or memoisation cache.'],
            ],
            'summary'  => "A closure keeps access to its lexical scope after the outer function returns.\nvar is function-scoped and hoisted; let/const are block-scoped.\nClosures enable private state and factories.",
            'practice' => 'Write a makeCounter() that returns increment and get functions.',
        ],
        'Binary Search Variants' => [
            'questions' => [
                ['question' => 'What loop invariant keeps binary search correct?', 'answer' => 'The answer, if it exists, is always inside [lo, hi].'],
                ['question' => 'How do you find the first index where a condition becomes true?', 'answer' => 'Lower-bound search: move hi = mid when the condition holds, lo = mid + 1 otherwise.'],
                ['question' => 'How do you search a rotated sorted array?', 'answer' => 'Find which half is sorted, then check whether the target lies inside that half.'],
            ],
            'summary'  => "Keep a clear invariant for [lo, hi].\nLower bound: first true of a monotone predicate.\nRotated array: one half is always sorted.",
            'practice' => 'Solve search-insert-position and rotated-array search without looking at notes.',
        ],
        'Promises & Async/Await' => [
            'questions' => [
                ['question' => 'What are the three states of a promise?', 'answer' => 'Pending, fulfilled, rejected.'],
                ['question' => 'Promise.all vs Promise.allSettled?', 'answer' => 'all rejects on the first failure; allSettled waits for every promise and reports each outcome.'],
                ['question' => 'How do you handle errors with async/await?'],
            ],
            'summary'  => "A promise is a placeholder for a future value.\nawait pauses the async function until the promise settles.\nUse try/catch around await; use allSettled when partial failure is fine.",
        ],
        'URL Shortener Design' => [
            'questions' => [
                ['question' => 'Why base62 for short codes?', 'answer' => 'URL-safe characters and short codes: 62^7 ≈ 3.5 trillion combinations.'],
                ['question' => 'Which data may be cached, and how is a cache miss handled?'],
                ['question' => 'How do you avoid two URLs getting the same code?', 'answer' => 'Use a unique ID generator (counter or snowflake) and encode it, or check-and-retry with a unique index.'],
            ],
            'summary'  => "Write path: generate unique ID → base62 code → store mapping.\nRead path: cache first, then DB, then 301/302 redirect.\nScale reads with caching and replicas.",
        ],
        'Sliding Window Technique' => [
            'questions' => [
                ['question' => 'When does a sliding window apply?', 'answer' => 'Contiguous subarray/substring problems where the window state can be updated incrementally.'],
                ['question' => 'Fixed vs variable window: what changes?', 'answer' => 'Variable windows shrink from the left while a constraint is violated.'],
                ['question' => 'What is the time complexity and why?', 'answer' => 'O(n): each element enters and leaves the window at most once.'],
            ],
            'summary'  => "Expand right, update state, shrink left while invalid, record the answer.\nEach index is visited at most twice, so the cost is O(n).",
            'practice' => 'Longest substring without repeating characters, from memory.',
        ],
    ];

    public function handle(): int
    {
        $password = config('app.demo_user_password', 'DemoPass@2026');

        DB::transaction(function () use ($password) {
            $user = $this->ensureDemoUser($password);
            $this->wipeUserData($user);
            $this->seedDemoData($user);
        });

        $this->info('Demo user reset successfully.');

        return self::SUCCESS;
    }

    private function ensureDemoUser(string $password): User
    {
        $user = User::where('is_demo', true)->first();

        if (! $user) {
            $user = User::where('email', self::DEMO_EMAIL)->first();
        }

        if ($user) {
            $user->forceFill([
                'name'              => self::DEMO_NAME,
                'email'             => self::DEMO_EMAIL,
                'password'          => Hash::make($password),
                'is_active'         => 1,
                'is_demo'           => true,
                'type'              => 3,
                'email_verified_at' => now(),
            ])->save();
        } else {
            $user = User::create([
                'name'              => self::DEMO_NAME,
                'email'             => self::DEMO_EMAIL,
                'password'          => Hash::make($password),
                'is_active'         => 1,
                'is_demo'           => true,
                'type'              => 3,
                'email_verified_at' => now(),
            ]);

            if (! $user->hasRole('User')) {
                $user->assignRole('User');
            }
        }

        return $user;
    }

    private function wipeUserData(User $user): void
    {
        PracticeLog::where('user_id', $user->id)->forceDelete();
        StudyTask::where('user_id', $user->id)->delete();
        Topic::where('user_id', $user->id)->forceDelete();
        Category::where('user_id', $user->id)->forceDelete();
        TopicRevisionTemplate::where('user_id', $user->id)->delete();
        CategoryReviewSchedule::where('user_id', $user->id)->delete();
        StudyWeek::where('user_id', $user->id)->delete();
    }

    private function seedDemoData(User $user): void
    {
        $categories = $this->createCategories($user);
        $revisionDays = $this->getRevisionDays();

        // Sample category schedule: Algorithms uses the long-horizon preset.
        $preset = config('study.schedule_presets.long_horizon');
        CategoryReviewSchedule::create([
            'user_id'           => $user->id,
            'category_id'       => $categories['Algorithms']->id,
            'preset_key'        => 'long_horizon',
            'offsets'           => $preset['offsets'],
            'repeat_every_days' => $preset['repeat_every_days'],
        ]);

        $topics = $this->getTopicDefinitions();

        foreach ($topics as $topicDef) {
            $category = $categories[$topicDef['category']];
            $this->createTopicWithTasks($user, $category, $topicDef, $revisionDays);
        }

        $this->createMistakes($user);
        $this->createWeeklyPlan($user);
    }

    /** A green plan for the current week with some blocks already marked. */
    private function createWeeklyPlan(User $user): void
    {
        $service = app(WeeklyPlanService::class);
        $prefs = StudyPreferences::for($user);
        $today = Carbon::today();

        $week = $service->create($user, [
            'week_start'  => $service->weekStart($today, $prefs)->toDateString(),
            'gear'        => 'green',
            'major_focus' => 'Algorithms: binary search and sliding window',
            'minor_focus' => 'One system design article a week',
        ]);

        $statuses = ['done', 'done', 'partial', 'done', 'missed', 'done'];
        $week->blocks()->whereDate('block_date', '<', $today->toDateString())->get()
            ->each(function (StudyBlock $block, int $i) use ($statuses) {
                $block->update(['status' => $statuses[$i % count($statuses)]]);
            });

        $week->blocks()->whereDate('block_date', $today->toDateString())->get()
            ->each(function (StudyBlock $block) {
                $block->update(['planned_task' => match ($block->slot) {
                    'morning', 'block_a' => 'Solve 3 rotated-array problems without notes',
                    'review'             => 'Due reviews on the Review page',
                    'minor', 'block_b'   => 'Read one rate-limiter design article',
                    default              => null,
                }]);
            });
    }

    /** One mistake still under review and one ready to merge into its parent topic. */
    private function createMistakes(User $user): void
    {
        $service = app(MistakeService::class);
        $closures = Topic::where('user_id', $user->id)->where('title', 'Closures & Scope')->first();
        $binarySearch = Topic::where('user_id', $user->id)->where('title', 'Binary Search Variants')->first();

        $service->create($user->id, [
            'question'        => 'What does this print: for (var i = 0; i < 3; i++) setTimeout(() => console.log(i))?',
            'my_answer'       => '0 1 2',
            'correct_answer'  => '3 3 3 — var is function-scoped, so all callbacks share one i.',
            'cause'           => 'concept',
            'source'          => 'Mock interview',
            'parent_topic_id' => $closures?->id,
            'logged_on'       => Carbon::today()->subDay()->toDateString(),
        ]);

        $ready = $service->create($user->id, [
            'question'        => 'Lower-bound search: when the condition holds at mid, which bound moves?',
            'my_answer'       => 'lo = mid + 1',
            'correct_answer'  => 'hi = mid (keep mid as a candidate).',
            'cause'           => 'careless',
            'source'          => 'LeetCode 35',
            'parent_topic_id' => $binarySearch?->id,
            'logged_on'       => Carbon::today()->subDays(8)->toDateString(),
        ]);

        StudyTask::where('topic_id', $ready->id)->get()->each(fn (StudyTask $task) => $task->update([
            'status'         => 'completed',
            'completed_at'   => $task->scheduled_date->copy()->setHour(20),
            'is_date_locked' => true,
            'recall_grade'   => 'good',
            'review_seconds' => rand(40, 120),
            'review_kind'    => StudyTask::REVIEW_KIND_STEP,
        ]));
        $ready->update(['srs_step' => 3, 'last_reviewed_on' => Carbon::today()->subDay()]);
    }

    private function createCategories(User $user): array
    {
        $defs = [
            'JavaScript'      => ['color' => '#f7df1e', 'icon' => 'fa-code'],
            'Data Structures' => ['color' => '#e74c3c', 'icon' => 'fa-layer-group'],
            'System Design'   => ['color' => '#3498db', 'icon' => 'fa-server'],
            'Algorithms'      => ['color' => '#2ecc71', 'icon' => 'fa-microchip'],
            'Web Development' => ['color' => '#9b59b6', 'icon' => 'fa-globe'],
        ];

        $result = [];
        foreach ($defs as $name => $attrs) {
            $result[$name] = Category::create([
                'user_id' => $user->id,
                'name'    => $name,
                'color'   => $attrs['color'],
                'icon'    => $attrs['icon'],
            ]);
        }

        return $result;
    }

    private function getRevisionDays(): array
    {
        $templates = TopicRevisionTemplate::whereNull('user_id')
            ->orderBy('sequence_no')
            ->get();

        if ($templates->isEmpty()) {
            return [1, 7, 30, 90];
        }

        return $templates->pluck('day_offset')->toArray();
    }

    private function getTopicDefinitions(): array
    {
        $today = Carbon::today();

        return [
            // Completed topics (learned 30-45 days ago, all revisions done)
            [
                'category'    => 'JavaScript',
                'title'       => 'Closures & Scope',
                'difficulty'  => 'medium',
                'status'      => 'completed',
                'first_study' => $today->copy()->subDays(45),
                'description' => 'Understanding closures, lexical scope, and variable hoisting in JavaScript.',
            ],
            [
                'category'    => 'Data Structures',
                'title'       => 'Arrays & Hash Maps',
                'difficulty'  => 'easy',
                'status'      => 'completed',
                'first_study' => $today->copy()->subDays(40),
                'description' => 'Array operations, hash map implementation, and collision handling.',
            ],

            // Mid-revision topics (learned 7-20 days ago, some revisions done)
            [
                'category'    => 'Algorithms',
                'title'       => 'Binary Search Variants',
                'difficulty'  => 'medium',
                'status'      => 'active',
                'first_study' => $today->copy()->subDays(20),
                'description' => 'Classic binary search, rotated arrays, search insert position.',
            ],
            [
                'category'    => 'JavaScript',
                'title'       => 'Promises & Async/Await',
                'difficulty'  => 'medium',
                'status'      => 'active',
                'first_study' => $today->copy()->subDays(14),
                'description' => 'Promise chaining, error handling, async/await patterns.',
            ],
            [
                'category'    => 'System Design',
                'title'       => 'URL Shortener Design',
                'difficulty'  => 'hard',
                'status'      => 'active',
                'first_study' => $today->copy()->subDays(10),
                'description' => 'Designing a scalable URL shortener service with base62 encoding.',
            ],
            [
                'category'    => 'Data Structures',
                'title'       => 'Binary Trees & Traversals',
                'difficulty'  => 'medium',
                'status'      => 'active',
                'first_study' => $today->copy()->subDays(8),
                'description' => 'Inorder, preorder, postorder traversals. BFS and DFS on trees.',
            ],

            // Recently learned topics (learned 1-3 days ago)
            [
                'category'    => 'Web Development',
                'title'       => 'REST API Best Practices',
                'difficulty'  => 'easy',
                'status'      => 'active',
                'first_study' => $today->copy()->subDays(2),
                'description' => 'HTTP methods, status codes, versioning, pagination, and HATEOAS.',
            ],
            [
                'category'    => 'Algorithms',
                'title'       => 'Sliding Window Technique',
                'difficulty'  => 'medium',
                'status'      => 'active',
                'first_study' => $today->copy()->subDays(1),
                'description' => 'Fixed and variable size sliding windows for substring/subarray problems.',
            ],

            // Future topics (scheduled for upcoming days)
            [
                'category'    => 'System Design',
                'title'       => 'Rate Limiter Design',
                'difficulty'  => 'hard',
                'status'      => 'active',
                'first_study' => $today->copy()->addDays(1),
                'description' => 'Token bucket, leaky bucket, fixed window, sliding window algorithms.',
            ],
            [
                'category'    => 'Web Development',
                'title'       => 'Authentication Patterns (JWT vs Sessions)',
                'difficulty'  => 'medium',
                'status'      => 'active',
                'first_study' => $today->copy()->addDays(3),
                'description' => 'Comparing JWT tokens, session cookies, OAuth2, and API keys.',
            ],
            [
                'category'    => 'Data Structures',
                'title'       => 'Graphs & BFS/DFS',
                'difficulty'  => 'hard',
                'status'      => 'active',
                'first_study' => $today->copy()->addDays(7),
                'description' => 'Graph representations, breadth-first and depth-first search, cycle detection.',
            ],
            [
                'category'    => 'JavaScript',
                'title'       => 'Event Loop & Microtasks',
                'difficulty'  => 'hard',
                'status'      => 'active',
                'first_study' => $today->copy()->addDays(10),
                'description' => 'Call stack, task queue, microtask queue, and rendering pipeline.',
            ],
        ];
    }

    private function createTopicWithTasks(User $user, Category $category, array $def, array $revisionDays): void
    {
        $topic = Topic::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'title'               => $def['title'],
            'slug'                => Str::slug($def['title']),
            'description'         => $def['description'],
            'difficulty'          => $def['difficulty'],
            'status'              => $def['status'],
            'first_study_date'    => $def['first_study'],
            'srs_offsets'         => $revisionDays,
            'srs_schedule_source' => 'system_default',
            'recall_questions'    => self::RECALL_CARDS[$def['title']]['questions'] ?? null,
            'summary'             => self::RECALL_CARDS[$def['title']]['summary'] ?? null,
            'practice_prompt'     => self::RECALL_CARDS[$def['title']]['practice'] ?? null,
            'lane'                => in_array($def['category'], ['System Design', 'Web Development'], true) ? 'work' : 'major',
        ]);

        $today = Carbon::today();
        $firstStudy = Carbon::parse($def['first_study']);

        // Create learn task
        $learnStatus = $firstStudy->lte($today) ? 'completed' : 'pending';
        $learnTask = StudyTask::create([
            'user_id'        => $user->id,
            'topic_id'       => $topic->id,
            'task_type'      => 'learn',
            'revision_no'    => null,
            'title'          => "Learn: {$def['title']}",
            'scheduled_date' => $firstStudy,
            'status'         => $learnStatus,
            'completed_at'   => $learnStatus === 'completed' ? $firstStudy->copy()->setHour(10) : null,
            'is_date_locked' => $learnStatus === 'completed',
        ]);

        // Create practice log for completed learn task
        if ($learnStatus === 'completed') {
            PracticeLog::create([
                'user_id'          => $user->id,
                'topic_id'         => $topic->id,
                'task_id'          => $learnTask->id,
                'practiced_on'     => $firstStudy,
                'practice_type'    => collect(['reading', 'note_making', 'implementation'])->random(),
                'details'          => "Initial study session for {$def['title']}.",
                'duration_minutes' => rand(25, 90),
                'outcome'          => collect(['good', 'okay', 'excellent'])->random(),
            ]);
        }

        // Create revision tasks
        foreach ($revisionDays as $index => $days) {
            $scheduledDate = $firstStudy->copy()->addDays($days);
            $revNo = $index + 1;

            // Determine status based on dates
            if ($scheduledDate->lte($today) && $learnStatus === 'completed') {
                $revStatus = 'completed';
            } elseif ($scheduledDate->lt($today) && $learnStatus === 'completed') {
                $revStatus = 'missed';
            } else {
                $revStatus = 'pending';
            }

            // For completed topics, all revisions are done
            if ($def['status'] === 'completed' && $scheduledDate->lte($today)) {
                $revStatus = 'completed';
            }

            $revTask = StudyTask::create([
                'user_id'        => $user->id,
                'topic_id'       => $topic->id,
                'task_type'      => 'revision',
                'revision_no'    => $revNo,
                'review_kind'    => StudyTask::REVIEW_KIND_STEP,
                'title'          => "Revision {$revNo}: {$def['title']}",
                'scheduled_date' => $scheduledDate,
                'status'         => $revStatus,
                'completed_at'   => $revStatus === 'completed' ? $scheduledDate->copy()->setHour(rand(9, 18)) : null,
                'is_date_locked' => $revStatus === 'completed',
                'parent_task_id' => $learnTask->id,
                'recall_grade'   => $revStatus === 'completed' ? collect(['good', 'good', 'easy', 'hard'])->random() : null,
                'review_seconds' => $revStatus === 'completed' ? rand(60, 300) : null,
            ]);

            if ($revStatus === 'completed') {
                $topic->srs_step = min($topic->srs_step + 1, count($revisionDays));
                $topic->last_reviewed_on = $scheduledDate;
            }

            // Create practice log for completed revisions
            if ($revStatus === 'completed') {
                PracticeLog::create([
                    'user_id'          => $user->id,
                    'topic_id'         => $topic->id,
                    'task_id'          => $revTask->id,
                    'practiced_on'     => $scheduledDate,
                    'practice_type'    => collect(['problem_solving', 'reading', 'implementation', 'note_making'])->random(),
                    'details'          => "Revision {$revNo} session for {$def['title']}.",
                    'duration_minutes' => rand(15, 60),
                    'outcome'          => collect(['good', 'excellent'])->random(),
                ]);
            }
        }

        $topic->save();
    }
}
