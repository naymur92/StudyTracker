<?php

namespace App\Services\StudyTracker;

use App\Exceptions\ActiveTimerConflictException;
use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use App\Models\User;
use App\Services\IdHasher;
use App\Services\StudyTracker\Scheduling\BlockTimerMath;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Timers on weekly-plan blocks. Each run is a StudyBlockSession; a user has at
 * most one active (running or paused) run. Outcomes are written when a run
 * ends: time used up → done, stopped early → partial.
 */
class BlockTimerService
{
    /** Stopping with this little left counts as finishing the block. */
    public const STOP_DONE_TOLERANCE_SECONDS = 30;

    /** Shorter runs are discarded when stopped. */
    public const MIN_RUN_SECONDS = 60;

    /** How far back `recent` looks for an ended run. */
    public const RECENT_HOURS = 12;

    /**
     * Start the block's timer, saving an optional topic and planned minutes first.
     *
     * @param  array{topic_id?: int|null, planned_minutes?: int|null}  $data
     *
     * @throws ValidationException|ActiveTimerConflictException
     */
    public function start(User $user, StudyBlock $block, array $data = []): StudyBlockSession
    {
        return $this->locked($user, function () use ($user, $block, $data) {
            $block->refresh();

            if (! $block->block_date->isSameDay(today())) {
                $this->fail('block_date', 'Only today\'s blocks can be started.');
            }
            if (! in_array($block->status, ['planned', 'partial'], true)) {
                $this->fail('status', 'Only a planned or partial block can be started.');
            }

            $active = $this->activeSession($user);
            if ($active) {
                throw new ActiveTimerConflictException($active);
            }

            $block->fill(array_filter([
                'topic_id' => $data['topic_id'] ?? null,
                'planned_minutes' => $data['planned_minutes'] ?? null,
            ], fn ($value) => $value !== null));

            if (! $block->planned_minutes) {
                $this->fail('planned_minutes', 'Set the block\'s minutes before starting it.');
            }
            if (! $block->topic_id && $block->lane !== 'review') {
                $this->fail('topic_id', 'Choose a topic before starting this block.');
            }
            if ($block->endedSeconds() >= $block->planned_minutes * 60) {
                $this->fail('planned_minutes', 'This block has no time left.');
            }

            $block->save();

            try {
                return StudyBlockSession::create([
                    'user_id' => $user->id,
                    'study_block_id' => $block->id,
                    'active_user_id' => $user->id,
                    'started_at' => now(),
                    'resumed_at' => now(),
                    'used_seconds' => 0,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new ActiveTimerConflictException($this->activeSession($user));
            }
        });
    }

    public function pause(User $user, StudyBlock $block): StudyBlockSession
    {
        return $this->locked($user, function () use ($block) {
            $session = $this->activeSessionOf($block);

            if ($session->isPaused()) {
                $this->fail('timer', 'This block\'s timer is already paused.');
            }

            $session->update([
                'used_seconds' => BlockTimerMath::runUsedSeconds($session->used_seconds, $session->resumed_at, now()),
                'resumed_at' => null,
            ]);

            return $session;
        });
    }

    public function resume(User $user, StudyBlock $block): StudyBlockSession
    {
        return $this->locked($user, function () use ($block) {
            $session = $this->activeSessionOf($block);

            if ($session->isRunning()) {
                $this->fail('timer', 'This block\'s timer is already running.');
            }
            if (! $block->block_date->isSameDay(today())) {
                $this->fail('block_date', 'A paused timer can be resumed only on the block\'s day. Stop or discard it.');
            }

            $session->update(['resumed_at' => now()]);

            return $session;
        });
    }

    /**
     * End the block's active run. Returns the outcome: `done`, `partial`, or
     * `discarded` for a run shorter than a minute.
     */
    public function stop(User $user, StudyBlock $block): string
    {
        return $this->locked($user, function () use ($block) {
            $session = $this->activeSessionOf($block);
            $runSeconds = BlockTimerMath::runUsedSeconds($session->used_seconds, $session->resumed_at, now());

            if ($runSeconds < self::MIN_RUN_SECONDS) {
                $session->delete();

                return 'discarded';
            }

            $prior = $block->endedSeconds();
            $planned = (int) $block->planned_minutes * 60;

            if ($planned - ($prior + $runSeconds) <= self::STOP_DONE_TOLERANCE_SECONDS) {
                $this->finish($session, $block, now(), max(0, $planned - $prior));

                return 'done';
            }

            $this->end($session, now(), $runSeconds, StudyBlockSession::END_STOPPED);
            $block->update(['status' => 'partial']);

            return 'partial';
        });
    }

    /** Remove the block's active run; its status and recorded time stay. */
    public function discard(User $user, StudyBlock $block): void
    {
        $this->locked($user, fn () => $this->activeSessionOf($block)->delete());
    }

    /**
     * Complete the user's running run if its time has run out, as of the
     * moment it ran out. Called before every read of timers or blocks.
     */
    public function settle(User $user): void
    {
        $running = StudyBlockSession::where('active_user_id', $user->id)->whereNotNull('resumed_at')->exists();
        if (! $running) {
            return;
        }

        DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->first();
            $this->settleLocked($user);
        });
    }

    /**
     * End the user's active run as `stopped` at now, without touching the
     * block's status. Used before a data deletion removes the block.
     */
    public function endActiveRun(User $user): void
    {
        $session = $this->activeSession($user);
        if ($session) {
            $this->end($session, now(), BlockTimerMath::runUsedSeconds($session->used_seconds, $session->resumed_at, now()), StudyBlockSession::END_STOPPED);
        }
    }

    public function activeSession(User $user): ?StudyBlockSession
    {
        return StudyBlockSession::where('active_user_id', $user->id)->with('block.topic')->first();
    }

    /** The user's latest run that ended in the last RECENT_HOURS hours. */
    public function recentSession(User $user): ?StudyBlockSession
    {
        return StudyBlockSession::where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->orderByDesc('ended_at')
            ->orderByDesc('id')
            ->with('block.topic')
            ->first();
    }

    /**
     * The timer object of an active run (without its block).
     *
     * @return array<string, mixed>
     */
    public function timerFields(StudyBlockSession $session, StudyBlock $block, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $planned = (int) $block->planned_minutes * 60;
        $used = min($planned, $block->endedSeconds() + BlockTimerMath::runUsedSeconds($session->used_seconds, $session->resumed_at, $now));
        $phase = BlockTimerMath::phaseAt($used, $planned, $block->break_every_minutes, $block->break_minutes);

        return [
            'session_id' => IdHasher::encode($session->id),
            'block_id' => IdHasher::encode($block->id),
            'state' => $session->isRunning() ? 'running' : 'paused',
            'started_at' => $session->started_at->toIso8601String(),
            'planned_seconds' => $planned,
            'used_seconds' => $used,
            'left_seconds' => max(0, $planned - $used),
            'phase' => $phase['phase'],
            'phase_left_seconds' => $phase['left'],
            'next_break_at_seconds' => BlockTimerMath::nextBreakAt($used, $planned, $block->break_every_minutes, $block->break_minutes),
            'break_every_minutes' => $block->break_every_minutes,
            'break_minutes' => $block->break_minutes,
        ];
    }

    /**
     * Run $callback in a transaction holding the user's row lock, after
     * settling a run whose time has run out. The settle commits on its own,
     * so a rejected action does not roll it back.
     */
    private function locked(User $user, callable $callback): mixed
    {
        $this->settle($user);

        return DB::transaction(function () use ($user, $callback) {
            User::whereKey($user->id)->lockForUpdate()->first();

            return $callback();
        });
    }

    private function settleLocked(User $user): void
    {
        $session = StudyBlockSession::where('active_user_id', $user->id)->whereNotNull('resumed_at')->with('block')->first();
        if (! $session) {
            return;
        }

        $block = $session->block;
        $prior = $block->endedSeconds();
        $planned = (int) $block->planned_minutes * 60;
        $runOutAt = BlockTimerMath::runOutAt($session->resumed_at, $session->used_seconds, $prior, $planned);

        if ($runOutAt->lte(now())) {
            $this->finish($session, $block, Carbon::parse($runOutAt), max(0, $planned - $prior));
        }
    }

    /** End the run with the block's full time and mark the block done. */
    private function finish(StudyBlockSession $session, StudyBlock $block, CarbonInterface $at, int $runSeconds): void
    {
        $this->end($session, $at, $runSeconds, StudyBlockSession::END_FINISHED);
        $block->update(['status' => 'done']);
    }

    private function end(StudyBlockSession $session, CarbonInterface $at, int $runSeconds, string $reason): void
    {
        $session->update([
            'active_user_id' => null,
            'resumed_at' => null,
            'used_seconds' => $runSeconds,
            'ended_at' => $at,
            'end_reason' => $reason,
        ]);
    }

    private function activeSessionOf(StudyBlock $block): StudyBlockSession
    {
        return $block->activeSession()->first() ?? $this->fail('timer', 'This block has no active timer.');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
