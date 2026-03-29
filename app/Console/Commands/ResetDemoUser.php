<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\PracticeLog;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Models\User;
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
    }

    private function seedDemoData(User $user): void
    {
        $categories = $this->createCategories($user);
        $revisionDays = $this->getRevisionDays();

        $topics = $this->getTopicDefinitions();

        foreach ($topics as $topicDef) {
            $category = $categories[$topicDef['category']];
            $this->createTopicWithTasks($user, $category, $topicDef, $revisionDays);
        }
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
            'user_id'          => $user->id,
            'category_id'      => $category->id,
            'title'            => $def['title'],
            'slug'             => Str::slug($def['title']),
            'description'      => $def['description'],
            'difficulty'       => $def['difficulty'],
            'status'           => $def['status'],
            'first_study_date' => $def['first_study'],
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
                'title'          => "Revision {$revNo}: {$def['title']}",
                'scheduled_date' => $scheduledDate,
                'status'         => $revStatus,
                'completed_at'   => $revStatus === 'completed' ? $scheduledDate->copy()->setHour(rand(9, 18)) : null,
                'is_date_locked' => $revStatus === 'completed',
                'parent_task_id' => $learnTask->id,
            ]);

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
    }
}
