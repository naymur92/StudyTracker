# Proposal

## Why

StudyTracker's spaced repetition is fixed. Every topic gets revisions at Day +1, +7, +30, +90 from its first study date, and nothing changes afterwards. A forgotten topic and an easy one get the same schedule, and a late review never moves the ones after it. The UI also sends `difficulty_feedback: "medium"` on every completion, so the app never learns how a review went.

The user's personal learning system ("Learn it all — one lane at a time", Oct 2026) and the learning-science guide behind it ("কার্যকর শেখার বিজ্ঞান") make StudyTracker the one place where every track meets. The system only works if each review is a *test* (retrieval practice), its outcome changes the next interval (successive relearning), and the daily load stays bounded (≈25 min). The guide asks for five specific features:

1. Recall grades that adapt the next interval.
2. Interval presets per category.
3. A "Due today: N topics ≈ M min" banner.
4. A question-first review mode.
5. A weekly view of planned and done blocks, measured against an 80% line.

The system starts on 7 Oct 2026, so the core review loop matters most.

The public features section still describes the old fixed schedule, and the app has no help pages. Neither visitors nor users can see why the app asks for grades or how it makes its decisions.

## What Changes

- **Recall grading and adaptive scheduling.** Completing a revision can carry a recall grade: Again, Hard, Good or Easy. The grade moves the topic through its schedule steps, Leitner-style:
  - Again restarts the steps and schedules a relearn check tomorrow.
  - Hard schedules a relearn check tomorrow and keeps progress.
  - Good advances one step.
  - Easy skips one step.
  The topic's remaining pending revisions are then re-planned from the actual review date. Completing without a grade keeps today's behavior: no re-plan.
- **Re-anchor on learning.** Completing a topic's Learn task on a different day than planned re-plans its revisions from the completion date. *(Modified behavior: today, revision dates never move.)*
- **Topic recall card.** Topics gain:
  - 0–10 recall questions, each with an optional answer.
  - A short summary that serves as the answer key.
  - A practice prompt.
  - A lane: Major, Minor or Work.
- **Question-first review session.** A new review page and endpoint serve today's due reviews, one per topic:
  - Overdue reviews come first, oldest first; today's reviews are then mixed across categories.
  - Each card shows the questions, hides the answers until "Reveal", previews the next date for each grade, records the time spent, and splits the queue at the daily time budget.
- **Review load.** A due-today estimate ("N topics ≈ M min") appears on the dashboard and the daily page, with a warning above the budget (default 25 min). Three soft guards come with it; none blocks the user:
  - A review-debt warning: more than 30 min of reviews due three days running.
  - A warning when this week's new topics pass the weekly cap (default 8).
  - A "never miss twice" prompt.
- **Study preferences.** Per-user settings: review budget, debt threshold, minutes per review, weekly new-topic cap, week start day, off days, and the success line (default 80%).
- **Revision schedules: presets and per-category schedules.**
  - A built-in preset library: Standard 1·7·30·90, Exam-soon 1·3·7·14 then weekly until a date, Long-horizon 1·3·7·21·60 then every 90 days, and Mistakes 1·3·7.
  - Per-user, per-category schedules that new topics in that category use.
  - Each topic snapshots its schedule at creation.
- **Mistakes notebook.** A wrong answer from a mock, exercise or interview is logged with:
  - the question
  - your answer
  - the correct answer
  - the cause (concept, memory or careless)
  - an optional parent topic

  Each entry is reviewed on the Mistakes schedule. Once it graduates, it can be merged into its parent topic's recall questions.
- **Weekly planning.**
  - A week has a gear: Green, Yellow or Red.
  - Planned blocks are generated from the gear template and the user's office/off days.
  - Each block has a lane and a pre-decided task, and is marked done, partial, missed or red day.
  - The week is scored as done ÷ planned against the success line; red days are excluded.
  - The page also shows the weekly reflection, the if-then plan and the overdue count, and the dashboard gets a "This week" widget.
- **Supporting changes.**
  - The daily agenda keeps its group keys but orders revision groups numerically.
  - The topics list hides mistakes by default and can filter by lane.
  - The report CSV gains a "Recall Grade" column.
  - Pages touched by this change compute "today" in local time, not UTC.
  - `difficulty_feedback` is still accepted but deprecated.
- **Features page and the science behind it.** A new public `/features` page lists:
  - the current features;
  - the learning techniques the app applies, with why and their benefits;
  - each decision algorithm, with a worked example, benefits, an evidence label and research references;
  - a full APA reference list.

  The Home and About summary (`AppTour.vue`) is refreshed and links to the new page.
- **User Guide.** A new `/app/guide` page for logged-in users. It has Getting started, a daily and weekly routine, and one section per menu, each with purpose, steps, tips, and the algorithms and techniques behind that menu. Every page links to its section.
- **Learning-science catalog.** One shared catalog of techniques, algorithms and references feeds both pages. Most references come from the two source documents; three additions are marked as such. A content check fails the build on an unknown, uncited or unverified reference, or on a menu with no guide section.
- No **BREAKING** API changes. Every new request field is optional, and every new response field is additive.

## Capabilities

### New Capabilities

- `spaced-repetition-scheduling`: Topic schedule state (step, lapses, last review). Covers:
  - the recall-grade transitions
  - re-planning pending revisions from the actual review date
  - repeat tails
  - re-anchoring on Learn completion
  - backward-compatible ungraded completion
- `revision-schedules`: The preset library, per-category schedules, the order in which a topic's schedule is resolved, and the snapshot taken at topic creation.
- `study-topics`: The topic recall card (questions and answers, summary, practice prompt), lanes, and topic list filtering by lane and kind.
- `review-sessions`: The due-review queue (ordering, one review per topic, mixing across categories, budget split, grade previews) and the question-first review flow with timing capture.
- `review-load`: The due-today estimate and budget warning, review-debt detection from daily load snapshots, the weekly new-topic cap, and the "never miss twice" prompt.
- `study-preferences`: Per-user study settings, their defaults and validation.
- `mistake-notebook`: Mistake entries, their schedule and review, and merging into a parent topic.
- `learning-science-catalog`: The techniques and algorithms the app uses, with benefits, evidence labels and verified research references.
- `features-page`: The public Features page and the refreshed Home and About summary.
- `user-guide`: The in-app user manual, with a section per menu, deep links and help links.
- `weekly-planning`: Weeks with gears, planned blocks from gear templates, block status tracking, the weekly score against the success line, the weekly reflection, and the dashboard summary.

### Modified Capabilities

None. `openspec/specs/` has no existing capability specs. Changes to current behavior (task completion, Learn re-anchoring, agenda ordering, topic list default) are written as requirements inside the new capabilities above.

## Impact

- **Database (new migrations).**
  - New columns on `topics`:
    - `recall_questions`, `summary`, `practice_prompt`, `lane`
    - `kind`, `parent_topic_id`, `mistake_details`, `merged_at`
    - `srs_step`, `srs_lapses`, `last_reviewed_on`
    - `srs_offsets`, `srs_repeat_every_days`, `srs_repeat_until`
  - New columns on `study_tasks`: `recall_grade`, `review_seconds`, `review_kind`.
  - A new column on `users`: `study_preferences`.
  - New tables: `category_review_schedules`, `review_load_snapshots`, `study_weeks`, `study_blocks`.
- **Backend.**
  - New services under `app/Services/StudyTracker/`: a pure scheduler, re-plan, schedule resolver, review queue, review load, weekly plan and mistakes.
  - Changes to `CompleteTaskService`, `CreateTopicWithPlanService`, `GenerateRevisionTasksService` and `BuildDailyAgendaService`.
  - New controllers, form requests and resources. `StudyWeek` and `StudyBlock` use `HashesIds`.
  - A new scheduled command, `study:snapshot-review-load` (daily at 00:03).
  - A new config file: `config/study.php`.
- **API (all under `/api/study`, with the `study-read`/`study-write` limiters and `deny.demo` on new mutating routes).**
  - `review-queue`, `review-load`, `preferences`, `schedule-presets`
  - `categories/{category}/schedule`
  - `mistakes` (CRUD and merge)
  - `weekly-plan` (with blocks and history)
  - New optional fields on `POST tasks/{task}/complete`.
  - New fields on topic, task and category resources and on the dashboard payload.
- **Frontend (Vue SPA).**
  - New pages: Review, Weekly Plan, Mistakes.
  - Changes to Dashboard, Daily Tasks, topic create/edit/detail, Categories, and Revision Templates (which becomes Study Settings).
  - New Pinia stores and navigation entries.
  - A public `/features` page and an in-app `/app/guide` page, built from content modules in `resources/js/content/`, with the navigation moved to `resources/js/config/navigation.js`.
  - A `scripts/check-learning-content.mjs` content check that runs before `npm run build`.
- **Ops and docs.**
  - The scheduler entry in `routes/console.php`.
  - Demo reset seeding for the new features.
  - `README.md`, `API-DOCUMENTATION.md`, the Postman collection and `CLAUDE.md`. This also fixes the stale revision-template paths in the API docs.
- **Delivery.** The work can ship in phases. The core review loop comes first (grading, recall card, review session, load banner), then schedules and mistakes, then weekly planning. Each phase leaves the app working.
