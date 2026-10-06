# Design

## Context

For motivation, see proposal.md → Why. For behavior, see `specs/*`. This document covers how to build it on the current code.

### Current state that shapes the approach

**Scheduling is fixed.**
- `CreateTopicWithPlanService` creates the topic and a Learn task.
- `GenerateRevisionTasksService` then writes every revision task up front, at `first_study_date + day_offset`, using `TopicRevisionTemplate::getForUser()` (the user's rows, else the system rows).
- `CompleteTaskService` only sets the task's status and lock fields.
- `difficulty_feedback` is stored but never read, and the SPA sends `'medium'` every time (`DashboardPage.vue`, `tasks/DailyPage.vue`).

**Agenda and missed tasks.**
- `study:mark-missed` (00:01) changes overdue `pending` tasks to `missed`.
- The agenda treats `pending` and `missed` tasks dated before today as overdue.
- `BuildDailyAgendaService` hard-codes the groups `revision_1` to `revision_4`. A `revision_5` group would be created on the fly and end up after `overdue`.
- The agenda, calendar and dashboard counts do not filter out tasks of soft-deleted topics.

**Data and API constraints.**
- Categories can be system rows (`user_id` null). Any per-user category setting must therefore be keyed on (user, category).
- There is no per-user settings store; `settings` is global key/value.
- Tests run on SQLite in memory. Enum columns there are CHECK constraints, so adding enum values later means rebuilding tables.
- The task actions (complete, skip, reschedule) deliberately lack `deny.demo` so the demo account can be used. The demo user is reset every night by `demo:reset`.

**Time.**
- The app timezone is `Asia/Dhaka`.
- Several SPA pages compute "today" with `toISOString()`, which is UTC. Between 00:00 and 06:00 local time they show the previous day.

## Goals / Non-Goals

**Goals:**
- Put scheduling decisions in a pure, deterministic calculator that can be unit-tested without a database. Grade previews and actual grading must share that one code path.
- Keep future reviews visible (calendar, topic detail) as an editable projection rather than generating only the next review.
- Stay backward compatible: no new package, only additive migrations, and only optional request fields.
- Make the core review loop shippable on its own (grading, recall card, review page, load banner); every later phase is additive.

**Non-Goals:**
- Translating the Features page or User Guide into Bangla, or a CMS for that content.
- SM-2 ease factors, FSRS, or any per-item memory model.
- Scheduling individual recall questions separately; grading stays per topic, as the guide's "one topic = one review" implies.
- Topics in more than one category. Tags remain the way to mark a second track.
- Reminders, push notifications, e-mail nudges.
- Generating recall questions automatically (for example with an LLM).
- User-editable gear templates or preset library; both live in config.
- Server-side enforcement of the review-debt and new-topic-cap rules. They are warnings only.
- New admin panel screens. Admin reports will count mistake entries as topics.

## Decisions

### D1. Leitner-style steps over the topic's own offsets

The topic stores `srs_step` = s, the number of schedule steps passed. The next review always tests step s+1. On review date D, with offsets `o[1..n]`:

| Grade | New step s′ | Next review | Kind |
|---|---|---|---|
| again | 0, and `srs_lapses` +1 | D + 1 | relearn |
| hard | s (no change) | D + 1 | relearn |
| good | min(s + 1, n) | D + (o[s′+1] − o[s+1]) if s′ < n | step |
| easy | min(s + 2, n) | D + (o[s′+1] − o[s+1]) if s′ < n | step |
| good/easy with s′ = n, or a tail review | n | D + r if a repeat interval r exists and the date ≤ repeat-until; otherwise none (graduated) | repeat |

If every review is graded `good` on its scheduled date, the dates equal today's fixed schedule, so existing users see no surprise. Because the step being tested is derived from topic state rather than from the task row, any pending task of the topic can be graded, including leftovers of the old pre-generated chain.

Code layout:
- `App\Services\StudyTracker\Scheduling\RecallScheduler` is a pure class with `grade(state, schedule, grade, date): Outcome` and `project(state, schedule, firstDate, firstKind): list<{date, kind}>`.
- `Schedule` is a small value object holding offsets, repeat interval and repeat-until date.
- `ScheduleState` is a small value object holding the step and lapse count.

Alternatives considered:
- **SM-2 ease factor.** The intervals become opaque and drift away from category presets and exam until-dates.
- **FSRS.** It needs per-user parameters and review history, which is overkill for this app, and the guide explicitly says a simple rule is enough.
- **Again moves back one step instead of restarting.** This is gentler, but it contradicts Leitner ("wrong → box 1") and the guide's successive-relearning advice. Hard already covers "shaky but mostly there".

### D2. Re-plan as projection plus reconcile

After an event that changes scheduling:
1. `RecallScheduler::project()` produces the expected sequence: the next review, then later steps assuming `good`, then at most one repeat review.
2. `ReplanRevisionsService` reconciles it with the topic's other revision tasks that are `pending` or `missed`, sorted by date and then id:
   - it updates existing rows in place (date, `status = pending`, `revision_no`, `review_kind`, title), which keeps IDs stable;
   - it inserts rows when the sequence is longer;
   - it deletes leftover rows when the sequence is shorter.
3. New `revision_no` values continue after the highest `revision_no` among completed and skipped revisions.
4. Titles are `Revision {n}: {topic}`, `Relearn check: {topic}` or `Maintenance review: {topic}`. `StudyTask::taskTypeLabel()` gains labels for the relearn and repeat kinds.

Re-planning happens after:
- a graded revision completion;
- a Learn completion while the topic has no completed revision.

It does not happen after an ungraded revision completion (which advances the step only), a skip, or a manual reschedule.

The whole completion runs in one DB transaction (`DB::transaction` with `Topic::lockForUpdate()`): status update, state change and reconcile. This makes double submits safe. The existing "already completed" check runs again after the lock is taken.

Alternatives considered:
- **Create only the next review (lazy).** The calendar and topic detail would lose all forward visibility, which is a big visible change.
- **Insert relearn tasks without moving others.** The pending tasks would stop matching the topic's state, and overdue backlogs would never collapse.
- **Soft-cancel leftover rows instead of deleting them.** This clutters the agenda and history with rows the user never acted on. Pending rows hold no user data worth keeping; `practice_logs.task_id` is `nullOnDelete`.

### D3. Topic state and schedule snapshot as topic columns, initialized lazily

New columns on `topics`:
- state: `srs_step` (unsigned tinyint, default 0), `srs_lapses` (unsigned smallint, default 0), `last_reviewed_on` (date, nullable);
- snapshot: `srs_offsets` (json, nullable), `srs_repeat_every_days` (unsigned smallint, nullable), `srs_repeat_until` (date, nullable), `srs_schedule_source` (string(30), nullable: `category`, `user_default`, `system_default`, `builtin` or `preset:mistakes`).

`TopicScheduleResolver::ensure(Topic)` handles topics created before this change. When `srs_offsets` is null, it resolves the schedule with the D5 order and stores it, and sets `srs_step` to the number of completed revisions (capped at n). It runs inside the completion transaction. No data migration touches existing rows.

Alternative: backfill everything in the migration. That makes a long-running, hard-to-reverse data migration for rows that may never be graded.

### D4. New fields are strings validated in the application, not DB enums

These new columns are short strings validated by Form Requests and constants on the model:
- `recall_grade`, `review_kind`
- `topics.kind`, `lane`
- block `slot`, `lane`, `status`
- week `gear`

The existing enums (`task_type`, `status`, `difficulty_feedback`) are left as they are. This avoids SQLite table rebuilds and enum migrations whenever a value is added. `difficulty_feedback` keeps working and is documented as deprecated.

### D5. Presets in config; category schedules in their own table

`config/study.php` holds:
- `schedule_presets`: the keys `standard`, `exam_short`, `long_horizon` and `mistakes`, each with a name, description, offsets and repeat interval;
- `preference_defaults`;
- `gear_templates`;
- `slot_order`.

Category schedules go in a new table `category_review_schedules`:
- columns: `id`, `user_id`, `category_id`, `preset_key`, `offsets` (json), `repeat_every_days`, `repeat_until`, timestamps;
- `unique(user_id, category_id)`;
- foreign keys with cascade on delete.

The table holds a whole schedule per row, so the repeat rule fits naturally, and system categories can carry a private schedule per user.

`ResolveScheduleService::forNewTopic(userId, categoryId, kind)` resolves the schedule in this order:
1. Mistakes use the `mistakes` preset.
2. The user's category schedule.
3. The user's active templates, via `TopicRevisionTemplate::getForUser()`; this also covers the system rows when the user has none.
4. The built-in offsets [1, 7, 30, 90].

`GenerateRevisionTasksService` now takes a resolved `Schedule`. When the schedule comes from templates it still uses the template step names in titles, so titles on the default path do not change.

`apply_to_existing_topics` only rewrites the snapshot columns. Dates move at each topic's next re-plan, which keeps the update a cheap bulk statement.

Alternatives considered:
- **Add `category_id` to `topic_revision_templates` step rows.** There is no clean place for the repeat rule, and the existing `PUT revision-templates` deletes all of the user's rows, so it would need rework.
- **Columns on `categories`.** These cannot hold per-user values for system categories.

### D6. Mistakes are topics with `kind = 'mistake'`

New columns on `topics`:
- `kind` (string(10), default `topic`, indexed with `user_id`);
- `parent_topic_id` (nullable foreign key to `topics`, null on delete);
- `mistake_details` (json: `my_answer`, `correct_answer`, `cause`, `source`);
- `merged_at` (timestamp, nullable).

Storing mistakes as topics reuses scheduling, grading, the review queue, the calendar, the agenda and reports unchanged.

`MistakeService` handles create, update and merge:
- On create, the title is `Str::limit(question, 200)`, the slug is unique with a `mistake-` prefix, `recall_questions` is `[{question, answer: correct_answer}]`, and `first_study_date` is `logged_on`. There is no Learn task; revisions come from the `mistakes` preset.
- The state is computed, not stored: `closed` if `merged_at` is set, otherwise `active` if a revision is pending or missed, otherwise `ready`.
- Merge runs in a transaction that locks the parent row. It does a case-insensitive, trimmed duplicate check and returns 422 when the parent already has 10 questions.

`TopicApiController@index` defaults to `kind = topic`. `MistakeApiController` binds `{mistake}` to `Topic` and responds 404 unless `kind === 'mistake'`.

Alternative: a separate `mistakes` table with its own task linkage. That duplicates scheduling, the queue and the review UI for no gain.

### D7. The review queue is computed on the server

`BuildReviewQueueService` does the following:
1. Loads due revision tasks: `pending` or `missed`, dated on or before the requested date, with the topic not soft-deleted and not archived. It eager-loads topic and category.
2. Keeps the earliest task per topic.
3. Puts overdue items first, oldest first.
4. Interleaves today's items round-robin across category names (alphabetical, uncategorized last).
5. Adds cumulative minutes and `within_budget` using the per-review estimate from `ReviewLoadService`.
6. Adds `grade_preview` from `RecallScheduler::grade()`, with the topic's schedule resolved in memory and not saved.

The SPA only renders the result. This keeps the rules in one tested place and lets other clients use them.

### D8. Review load uses daily snapshots for debt history

`ReviewLoadService::forDate(user, date)` returns the live estimate:
- `due_topics` and `overdue_topics` are counts of distinct topics;
- `per_review_minutes` is the mean `review_seconds` of the last 30 timed graded reviews (at least 5 samples, clamped to 1–15), else the `minutes_per_review` preference;
- `estimated_minutes = ceil(due_topics × per_review_minutes)`;
- `new_topics_this_week`;
- `never_miss_twice`, computed only from tasks: no revision `completed_at` yesterday, and at least one revision dated on or before yesterday still pending or missed.

Debt history:
- A new table `review_load_snapshots` has columns `user_id`, `snapshot_date`, `due_topics`, `estimated_minutes` and `unique(user_id, snapshot_date)`.
- A new command `study:snapshot-review-load` upserts today's row for every user with due revisions. It is scheduled daily at 00:03, after `study:mark-missed`.
- `ReviewLoadService` also upserts today's row on first computation if it is missing. This covers environments where the scheduler is not running.
- Debt is computed from the last 15 snapshot days: find the latest run of three consecutive calendar days above the threshold, then check whether any later day, or today's *live* estimate, is below the budget.

Alternatives considered:
- **Reconstruct past load from task rows.** Re-plans rewrite dates, so the history would be wrong.
- **Cache.** It is lost when the cache is cleared and cannot be tested.

### D9. Preferences in a JSON column on `users`

`users.study_preferences` is a nullable JSON column. `StudyPreferences::for(User)` merges it over `config('study.preference_defaults')`. `UpdateStudyPreferencesRequest` validates the values, including the threshold ≥ budget check against the merged result. PUT merges only the keys it receives.

A JSON column fits a small, fixed schema with one row per user and needs no new model.

Alternatives considered:
- **A `study_preferences` table.** It costs an extra model and join for seven scalar values.
- **The global `settings` table.** It is not per-user.

### D10. Weekly planning tables; the score is computed on read

**Tables.**
- `study_weeks`: `id`, `user_id`, `week_start` (date), `gear`, `major_focus`, `minor_focus`, `reflection` (text), `if_then_plan`, `output_note`, timestamps; `unique(user_id, week_start)`.
- `study_blocks`: `id`, `user_id`, `study_week_id` (cascade on delete), `block_date`, `slot`, `lane`, `planned_task`, `planned_minutes`, `status` (default `planned`), `note`, `category_id` (null on delete), `topic_id` (null on delete), timestamps; index on (`user_id`, `block_date`).
- Both models use `HashesIds`.

**Week lookup.** `week_start = date − ((dow(date) − week_starts_on + 7) mod 7)` days. Lookup takes the user's latest week with `week_start ≤ date` and `week_start + 6 ≥ date`, so weeks created before a change to `week_starts_on` still resolve. On create, the system validates that the weekday matches the current preference and that no existing week overlaps.

**Block generation.** `WeeklyPlanService::generateBlocks(week, gear, fromDate)` walks the days of the week (only those on or after `fromDate` when regenerating) and applies the config template for office or off days. For Yellow, block A goes only on the first off day of the week.

**Score.** The score is computed on every read, so a late status change is always reflected. Counted blocks are those that are not `red` and either `block_date < today` or have status `done`, `partial` or `missed`. A past `planned` block counts as missed. History for up to 26 weeks loads all blocks of those weeks in one query and groups them in PHP.

### D11. API surface follows the existing conventions

All routes are in the `auth:api` → `study` group. Every response goes through `jsonResponse()`. Form Requests decode hashed IDs in `prepareForValidation()`. Ownership checks follow the existing pattern: 403 for another user's row, 404 for a bad hash.

| Route | Limiter | Demo users |
|---|---|---|
| `GET review-queue`, `GET review-load`, `GET preferences`, `GET schedule-presets`, `GET categories/{category}/schedule`, `GET mistakes`, `GET weekly-plan`, `GET weekly-plan/history` | study-read | allowed |
| `PUT preferences`, `PUT`/`DELETE categories/{category}/schedule`, `POST`/`PATCH`/`DELETE mistakes…`, `POST mistakes/{mistake}/merge`, `POST`/`PATCH`/`DELETE weekly-plan…`, `POST weekly-plan/{week}/regenerate`, `POST weekly-plan/{week}/blocks`, `PATCH`/`DELETE blocks/{block}` | study-write | `deny.demo` |
| `POST tasks/{task}/complete` (existing; new optional fields) | study-write | allowed, as today |

`GET weekly-plan/history` is registered before any `weekly-plan/{week}` route.

The completion response's `data` is the StudyTaskResource array merged with `schedule_outcome`. The controller builds this array, so the shared resource stays unchanged for other endpoints.

### D12. Frontend structure

**New pages.**
- `pages/review/ReviewPage.vue` (`/app/review`)
- `pages/weekly/WeeklyPlanPage.vue` (`/app/week`)
- `pages/mistakes/MistakesPage.vue` (`/app/mistakes`)

**New components.**
- `DueTodayBanner.vue`
- `GradePicker.vue`: a modal used by the Dashboard and Daily Tasks pages
- `RecallQuestionsEditor.vue`
- `ScheduleSummary.vue`
- `ThisWeekWidget.vue`
- `MistakeForm.vue`: used by the Mistakes page and the review page

**New stores.** `review.js`, `weeklyPlan.js`, `mistakes.js`, `preferences.js`. `categories.js` gains schedule actions.

**Navigation.** Add Review, Weekly Plan and Mistakes. Relabel "Revision Templates" as "Study Settings"; the route stays the same, and the page gains Preferences and Presets sections.

**Local date.** Add a `helpers/dates.js` `todayLocal()` (date-fns `format(new Date(), 'yyyy-MM-dd')`). Use it on every page this change touches.

### D13. Small consistency fixes inside the touched code

- `BuildDailyAgendaService`, the calendar and the dashboard counts add `whereHas('topic')` to exclude tasks of soft-deleted topics.
- Agenda groups are sorted as `learn`, then `revision_N` by N, then `practice`, then `overdue`.
- The report CSV gets a "Recall Grade" column appended at the end of the task rows, so existing column positions do not move.
- `API-DOCUMENTATION.md` revision-template paths are corrected; there is no `{userId}` segment.

### D14. One content catalog, rendered by both pages

Content lives in plain ES modules:
- `resources/js/content/learningScience.js` exports `references`, `techniques` and `algorithms`;
- `resources/js/content/userGuide.js` exports guide sections keyed by navigation name.

`FeaturesPage.vue` (`/features`, public, lazy-loaded) and `UserGuidePage.vue` (`/app/guide`, lazy-loaded) render these through small components: `TechniqueCard`, `AlgorithmCard`, `CitationLink` and `ReferenceList`. Each page renders its own reference list, so citations resolve inside the page.

The sidebar items move to `resources/js/config/navigation.js`. Each item gets a `guideSection`, which drives both the guide's coverage check and the `HelpLink` shown on every page header. The router's `scrollBehavior` honours `to.hash` so deep links work. The public header gains a "Features" link, and the broken mobile `/app/dashboard` link is fixed to `/app`.

Alternatives considered:
- **A database or CMS.** Content would need admin screens and migrations, and it should change together with the code it describes.
- **Markdown files.** These need a new parser dependency and sanitizing.
- **Text duplicated in each page.** The two copies would drift apart.

### D15. Content check fails the build

`scripts/check-learning-content.mjs` is a dependency-free Node script, run as `npm run check:content` and as `prebuild`. It checks that:
- every cited id exists and every reference is cited;
- every reference has `verified: true`;
- every technique and algorithm has an evidence label, at least two benefits and at least one reference;
- every navigation item has a guide section;
- every id a guide section uses exists.

CI and deploy already run `npm run build`, so content errors block them. Adding Vitest was considered but would be a new toolchain just for this.

### D16. Evidence and citation policy, and the content plan

Evidence labels:
- `Research-backed`: the core mechanism has direct experimental or meta-analytic support.
- `Rule of thumb`: a heuristic that research motivates.

Limits are stated in the entries; for example, interleaving helps most for similar material (Brunmair & Richter 2019). Numbers are quoted only as the source documents state them.

References come from the two source documents ("Learn it all — one lane at a time", "কার্যকর শেখার বিজ্ঞান"), plus three additions: Leitner (1972), Butler & Roediger (2008) and Metcalfe (2017). Each reference is checked against its publication record before it is marked `verified`.

| Algorithm | Evidence | References |
|---|---|---|
| Adaptive review scheduler | Research-backed | Leitner 1972; Cepeda et al. 2006; Rawson et al. 2013; Dunlosky et al. 2013 |
| Relearn check | Research-backed | Rawson et al. 2013; Mazza et al. 2016 |
| Interval presets and exam-date repeat | Research-backed | Cepeda et al. 2008 |
| Due-review queue | Rule of thumb | Cepeda et al. 2006; Brunmair & Richter 2019 |
| Review-time estimate and budget | Rule of thumb | Buehler et al. 1994 |
| Review-debt detection | Rule of thumb | Cepeda et al. 2006; Buehler et al. 1994 |
| Never-miss-twice prompt | Rule of thumb | Lally et al. 2010 |
| Weekly gear blocks | Rule of thumb | Buehler et al. 1994; Gollwitzer & Sheeran 2006; Newport 2016 |
| Weekly consistency score | Rule of thumb | Lally et al. 2010 |
| Mistake schedule and merge | Research-backed | Metcalfe 2017; Butler & Roediger 2008; Rawson et al. 2013 |

| Technique | References |
|---|---|
| Retrieval practice | Roediger & Karpicke 2006; Karpicke & Blunt 2011; Rowland 2014; Adesope et al. 2017; Dunlosky et al. 2013; Donoghue & Hattie 2021 |
| Spaced repetition | Ebbinghaus 1885/1913; Murre & Dros 2015; Cepeda et al. 2006, 2008 |
| Successive relearning | Rawson et al. 2013 |
| Feedback after retrieval | Butler & Roediger 2008; Richland et al. 2009 |
| Desirable difficulties and generation | Bjork 1994; Slamecka & Graf 1978; Soderstrom & Bjork 2015; McDaniel et al. 2009 |
| Interleaving (with caveat) | Rohrer & Taylor 2007; Kornell & Bjork 2008; Brunmair & Richter 2019 |
| Implementation intentions | Gollwitzer & Sheeran 2006 |
| Realistic planning | Buehler et al. 1994 |
| Consistency over perfection | Lally et al. 2010 |
| Single-track focus | Leroy 2009; Leroy & Glomb 2018; Newport 2016 |
| Learning from errors | Metcalfe 2017 |

The guide's tip on evening reviews cites Diekelmann & Born (2010) and Mazza et al. (2016).

## Risks / Trade-offs
- **A citation is wrong or overstated.** → Each reference must be marked `verified` after checking it against its publication record, and the content check blocks unverified ones. Evidence labels and stated limits keep the claims modest.
- **The guide drifts from the app's behavior.** → Each feature group updates its own guide section, the content check catches missing sections, and the final group compares every worked example with the tests.

- **A re-plan overwrites a manually rescheduled pending date.** → Documented in the API docs. The reschedule modal tells the user that grading may move later reviews.
- **Leftover pending rows are deleted.** Notes on pending rows are lost, and any practice log pointing at one gets `task_id = null`. → Only `pending`/`missed` rows of the same topic are touched, and history is never deleted.
- **`revision_no` is an unsigned tinyint (max 255).** → Growth is slow: a 5-step schedule plus a 90-day repeat for 10 years is about 45. Number assignment caps at 255. Widening the column is left to a follow-up if it is ever needed.
- **Legacy topics adopt the *current* templates at their first grade,** which may differ from what generated them. → The first grade re-plans from that moment anyway, so the result is internally consistent.
- **Learn re-anchoring changes dates for API clients that complete Learn late.** → It only applies while no revision has been completed, and it is documented as changed behavior.
- **Debt detection needs snapshots.** With no scheduler and no visits it simply stays inactive. → Lazy upsert, and a failure only means no warning.
- **Mistakes inflate admin topic counts.** → Accepted. A separate admin breakdown can come later.
- **Demo users cannot try weekly-plan or mistake writes** (CLAUDE.md requires `deny.demo`). → `demo:reset` seeds a current week, mistakes, graded history and a category schedule so those screens have content.
- **Interleaving changes the familiar grouped order on the review page only.** → The Daily Tasks agenda keeps its grouped view.
- **Browser timezone different from Asia/Dhaka:** around midnight, local "today" and server "today" can differ. → Server dates are authoritative for scheduling; the audience is in one timezone.

## Migration Plan

1. **Migrations.** All are additive, and each has a `down()` that drops what it added:
   1. `topics`: recall card, lane, kind/mistake and schedule columns.
   2. `study_tasks`: `recall_grade`, `review_seconds`, `review_kind`.
   3. `users`: `study_preferences`.
   4. New table `category_review_schedules`.
   5. New table `review_load_snapshots`.
   6. New tables `study_weeks` and `study_blocks`.
   There is no data backfill; legacy topics initialize lazily (D3).
2. **Config.** Add `config/study.php`. `deploy.sh` already runs `config:cache` after `migrate --force`.
3. **Scheduler.** Register `study:snapshot-review-load` daily at 00:03 in `routes/console.php`. The production cron already runs `schedule:run`.
4. **Frontend.** `npm run build` in CI and on deploy; no new `VITE_*` variables.
5. **Rollback.** Revert the code and run `php artisan migrate:rollback --step=6`. Re-planned task rows are ordinary task rows, so old code keeps working with them. Data in the new columns and tables is lost on rollback.
6. **Phasing.** The task list is ordered so each phase can be deployed on its own:
   1. foundation, scheduling, recall card, review page, load banner
   2. presets and category schedules
   3. mistakes
   4. weekly planning
   5. demo data and docs

## Open Questions

- Exact UI copy and colors for the banner, the debt warning and the gear chips. Decide while building the UI; the spec only fixes the meaning.
- Whether to remove `difficulty_feedback` from the API in a later change, after external clients have moved to `recall_grade`.
- Whether admin reports should show mistakes separately from topics. This is a follow-up and does not affect this change.
