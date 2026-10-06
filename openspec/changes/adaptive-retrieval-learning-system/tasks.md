# Tasks

Phases are ordered so each one can be deployed on its own:
- groups 1–7: the core review loop
- group 8: the Features page, User Guide and learning-science catalog
- group 9: schedules
- group 10: mistakes
- group 11: weekly planning
- group 12: integration checks

Feature tests use `getJson`/`postJson`/`putJson`/`patchJson`, because the `api.headers` middleware requires JSON `Accept` and `Content-Type` headers.

## 1. Foundation: config, schema, models, test support

- [x] 1.1 Create `config/study.php` with:
  - `schedule_presets`: `standard` [1,7,30,90]; `exam_short` [1,3,7,14] repeating every 7 days; `long_horizon` [1,3,7,21,60] repeating every 90 days; `mistakes` [1,3,7]
  - `preference_defaults`
  - `gear_templates` for green, yellow and red, each with office-day and off-day blocks
  - `slot_order`
  - review constants: at most 10 recall questions; timing sample of 30 reviews with a minimum of 5; per-review minutes clamped to 1–15

  Verify that `php artisan config:show study` prints the four presets with these offsets.
- [x] 1.2 Add a migration for the new `topics` columns:
  - recall card: `recall_questions` (json), `summary`, `practice_prompt`, `lane`
  - mistakes: `kind` (default `topic`), `parent_topic_id` (null on delete), `mistake_details` (json), `merged_at`
  - schedule state: `srs_step`, `srs_lapses`, `last_reviewed_on`
  - schedule snapshot: `srs_offsets` (json), `srs_repeat_every_days`, `srs_repeat_until`, `srs_schedule_source`
  - an index on (`user_id`, `kind`)

  Use plain string columns for the new enumerations (design D3, D4, D6). Verify that `php artisan migrate`, then `php artisan migrate:rollback --step=1`, then `php artisan migrate` all succeed locally.
- [x] 1.3 Add a migration for `study_tasks.recall_grade`, `study_tasks.review_seconds`, `study_tasks.review_kind` and `users.study_preferences` (json). Verify the same migrate → rollback → migrate round trip.
- [x] 1.4 Add migrations that create:
  - `category_review_schedules`, with unique (user, category)
  - `review_load_snapshots`, with unique (user, snapshot_date)
  - `study_weeks`, with unique (user, week_start)
  - `study_blocks`, with an index on (`user_id`, `block_date`)

  Use the columns in design D5, D8 and D10. Verify the round trip, and that `php artisan db:table study_blocks` lists the expected columns.
- [x] 1.5 Update and add models:
  - `Topic`: fillable fields; array/date casts; `KINDS` and `LANES` constants; `parentTopic`/`mistakes` relations; `regular()`/`mistakes()` scopes
  - `StudyTask`: fillable fields and casts; `GRADES` and `REVIEW_KINDS` constants
  - `User`: array cast for `study_preferences`
  - new: `CategoryReviewSchedule`, `ReviewLoadSnapshot`, `StudyWeek` and `StudyBlock`, the last two using `HashesIds`

  Verify that `composer test` still passes.
- [x] 1.6 Add factories:
  - `Category`
  - `Topic`, with a `mistake` state
  - `StudyTask`, with `learn`, `revision`, `completed` and `missed` states
  - `StudyWeek` and `StudyBlock`

  Add a `tests/Feature/Study/StudyApiTestCase` base class using `RefreshDatabase` and `Passport::actingAs()`, which seeds the system revision templates. Verify with a smoke test that `getJson('/api/study/dashboard')` returns 200 with `flag: true`.
- [x] 1.7 Update the `README.md` "Database Schema" section with the new columns and tables. Verify that every table created in 1.2–1.4 is listed.

## 2. Study preferences

- [x] 2.1 Implement a `StudyPreferences` service that merges `users.study_preferences` over `config('study.preference_defaults')`, with typed getters. Verify with a unit test that a user without saved values gets every default listed in the study-preferences spec.
- [x] 2.2 Add `UpdateStudyPreferencesRequest` with:
  - the range rules from the spec
  - distinct `off_days` values
  - the threshold ≥ budget check, applied to the merged values

  Add `StudyPreferenceApiController` with `GET` and `PUT /api/study/preferences`: `study-read`/`study-write` limiters, `deny.demo` on PUT, and partial merge on PUT. Verify with feature tests: default read, partial update, threshold below budget (422), invalid off day (422), demo user (403).
- [x] 2.3 Add `stores/preferences.js` and a Preferences section on the settings page, with errors shown next to each field. Relabel the nav item "Revision Templates" as "Study Settings" (the route does not change). Verify by hand that a budget of 20 survives a page reload, and that `npm run build` passes.
- [x] 2.4 Document the preferences endpoints in `API-DOCUMENTATION.md`, in the `README.md` endpoint table and in the Postman collection. Verify that `python3 -m json.tool StudyTracker-API.postman_collection.json` succeeds.

## 3. Scheduling engine

- [x] 3.1 Implement the `Schedule`, `ScheduleState` and `ScheduleOutcome` value objects and the pure `RecallScheduler::grade()` (design D1 table). Verify that `tests/Unit/RecallSchedulerTest` covers Good, Easy (including the cap), Hard, Again, the repeat tail with an until date, and graduation, using the exact dates from the spaced-repetition-scheduling spec.
- [x] 3.2 Implement `RecallScheduler::project()`: the next review, then later steps assuming Good, then at most one repeat review. Verify with unit tests:
  - "Again expands the remaining plan": 2026-11-07, 2026-11-13, 2026-12-06, 2027-02-04
  - "Learned three days late": 2026-10-11, 2026-10-17, 2026-11-09, 2027-01-08
  - on-time Good reviews give the same dates as the fixed schedule
- [x] 3.3 Implement two services:
  - `ResolveScheduleService::forNewTopic()`, resolving in this order: mistakes preset, the user's category schedule, the user's or system templates, then the built-in offsets;
  - `TopicScheduleResolver::ensure()`, which takes the snapshot lazily and initializes the step for legacy topics.

  Verify with feature tests:
  - a category schedule wins over templates;
  - the fallback uses the user's templates [2, 10];
  - a legacy topic with two completed revisions resolves to offsets [1, 7, 30, 90] with step 2.

## 4. Grading, re-planning and topic creation

- [x] 4.1 Implement `ReplanRevisionsService` (design D2):
  - update rows in place, insert missing rows, delete leftover rows;
  - reset re-dated `missed` rows to `pending`;
  - number rows after the highest completed or skipped `revision_no`;
  - set titles and `review_kind`.

  Extend `StudyTask::taskTypeLabel()` for relearn and repeat reviews. Verify with the feature tests "Backlog collapses after a review", "Completed history is preserved" and "Numbering continues after history".
- [x] 4.2 Extend `CompleteTaskRequest` with `recall_grade` and `review_seconds` (1–3600); a grade is only valid on revision tasks. Change `CompleteTaskService` to:
  - run in a transaction with `lockForUpdate` on the topic, and check "already completed" again after the lock;
  - call `ensure()`;
  - on a graded completion, re-plan;
  - on an ungraded completion, advance the step by one without re-planning;
  - re-anchor on Learn completion only while no revision is completed;
  - set `last_reviewed_on`.

  Verify feature tests for every completion scenario in the spaced-repetition-scheduling spec, including the 422 cases and the double submit.
- [x] 4.3 Return `schedule_outcome` from `StudyTaskApiController@complete` (design D11). Add `recall_grade`, `review_seconds` and `review_kind` to `StudyTaskResource` and to the agenda's task format. Verify with a feature test that asserts `schedule_outcome.next_review_date` and `graduated`.
- [x] 4.4 Refactor `CreateTopicWithPlanService` and `GenerateRevisionTasksService` to resolve and snapshot the schedule, and to generate step tasks plus one repeat review. Verify with two tests:
  - regression: a default user still gets +1/+7/+30/+90 revisions with the same titles as before;
  - an `exam_short` category schedule (row inserted directly) gives 2026-10-12, 2026-10-14, 2026-10-18 and 2026-10-25, plus a repeat on 2026-11-01, for first study date 2026-10-11.
- [x] 4.5 Add to `TopicResource`: `srs_step`, `srs_steps_total`, `srs_lapses`, `last_reviewed_on`, `next_review_date` (via `withMin` over pending/missed revisions) and a schedule summary. Verify:
  - a feature test on `GET /api/study/topics/{topic}`;
  - a query-count test showing the topics index does not make one query per topic.
- [x] 4.6 Order agenda groups as `learn` → `revision_N` (ascending) → `practice` → `overdue`. Exclude tasks of soft-deleted topics from the agenda, the calendar and the dashboard counts. Verify with the feature tests "Revision numbers above four" and "Topic deleted with pending revisions" (agenda and dashboard parts).
- [x] 4.7 Append a "Recall Grade" column to the task rows of the report CSV. Verify by extending `BuildStudyReportServiceTest` to assert the column, and that it still passes.
- [x] 4.8 Update the documentation:
  - `API-DOCUMENTATION.md` and Postman "Mark Task Complete": `recall_grade`, `review_seconds`, `schedule_outcome`, re-planning, Learn re-anchoring, the deprecated `difficulty_feedback`, and the note that manual reschedules may be overwritten;
  - the revision-template paths in `API-DOCUMENTATION.md`, which wrongly include `{userId}`;
  - the README feature bullets;
  - the "Domain core" paragraph of `CLAUDE.md`.

  Verify that the Postman JSON parses.
- [x] 4.9 Extend `demo:reset` to store schedule snapshots on demo topics, and `recall_grade`/`review_seconds` on completed demo revisions. Verify that `php artisan demo:reset` succeeds twice in a row and the demo dashboard loads.

## 5. Topic recall card

- [x] 5.1 Add these fields to `StoreTopicRequest` and `UpdateTopicRequest`, to create/update persistence and to `TopicResource`:
  - `recall_questions`: at most 10 items; `question` required, ≤500 characters; `answer` ≤2000 characters
  - `summary`: ≤2000 characters
  - `practice_prompt`: ≤500 characters
  - `lane`

  Also add `kind` and `parent_topic_id` to `TopicResource`. Verify with feature tests: three questions saved in order; 11 questions → 422; an empty question → 422 naming the item; an invalid lane → 422.
- [x] 5.2 Add a `lane` filter and a `kind` filter (`topic` by default, `mistake`, `all`) to `IndexTopicRequest` and `TopicApiController@index`. Verify the tests "Default list hides mistakes" and "Filter by lane".
- [x] 5.3 Build `RecallQuestionsEditor.vue`. Add recall questions (with the "3–5 work best" hint), summary, practice prompt and lane to the topic create and edit pages. Verify by hand that a topic created with three questions shows them on its detail page, and that `npm run build` passes.
- [x] 5.4 Extend the topic detail page with:
  - the recall card, with answers collapsed;
  - the summary and practice prompt;
  - a lane chip;
  - `ScheduleSummary.vue`, "Step x of n", the lapse count and the next review date;
  - relearn and repeat badges in the task list.

  Verify by hand on a graded topic, and that `npm run build` passes.
- [x] 5.5 Seed recall questions, summaries and lanes on demo topics in `demo:reset`. Document the new topic fields in the API docs (Create Topic sample and field map) and in Postman. Verify that the Postman JSON parses and that a demo topic's detail page shows its questions.

## 6. Review load

- [x] 6.1 Implement the live estimate in `ReviewLoadService`:
  - count distinct due and overdue topics, leaving out archived and deleted ones;
  - per-review minutes: the mean of the last 30 timed grades if there are at least 5, clamped to 1–15, otherwise the user's preference;
  - round the estimate up and set `over_budget`;
  - compute `new_topics_this_week` from the user's week start;
  - compute `never_miss_twice`.

  Verify unit and feature tests for the review-load scenarios: under budget (18 min), over budget (30 min), the history-based estimate of 2.5, mistakes not counted, missed yesterday, reviewed yesterday.
- [x] 6.2 Implement daily snapshots:
  - a `study:snapshot-review-load` command that upserts a row for each user with due revisions;
  - schedule it at 00:03 in `routes/console.php`;
  - lazily upsert today's row when the load is computed and no row exists.

  Verify with a feature test that running the command twice creates one row per user, and that `php artisan schedule:list` shows the entry.
- [x] 6.3 Implement review-debt detection from the last 15 snapshot days plus today's live estimate, returning `review_debt_active` and `review_debt_since`. Verify the tests "Three heavy days", "Debt clears under budget" and "A gap breaks the run".
- [x] 6.4 Add `GET /api/study/review-load` (`study-read`) and `stats.review_load` in the dashboard payload. Verify feature tests for both responses.
- [x] 6.5 Build the load UI:
  - `DueTodayBanner.vue` on the Dashboard and Daily Tasks pages: warning style with carry-over advice when over budget, and a "nothing due" state;
  - the debt warning on the Dashboard and the topic create page;
  - the weekly-cap warning on the topic create page;
  - the never-miss-twice prompt on the Dashboard.

  Switch these pages to a `helpers/dates.js` `todayLocal()`. Verify by hand with over-budget data that warnings appear and topic creation still succeeds, and that `npm run build` passes.
- [x] 6.6 Document the review-load endpoint and the dashboard field in the API docs and Postman. Add the snapshot command to "Scheduled Commands" in the README and to the scheduled-commands line in `CLAUDE.md`. Verify that the Postman JSON parses.

## 7. Review queue and review session

- [x] 7.1 Implement `BuildReviewQueueService` (design D7):
  - due pending or missed revisions on or before the date, leaving out archived and deleted topics;
  - only the earliest due review per topic;
  - overdue items first, oldest first;
  - today's items round-robin across categories in name order, uncategorized last;
  - `cumulative_minutes` and `within_budget`, with the first item always within budget;
  - `grade_preview` from `RecallScheduler`;
  - item content, including mistake details.

  Verify unit and feature tests for each queue scenario in the review-sessions spec: due vs future, archived excluded, one per topic, overdue ordering, A1-B1-A2 interleave, 12 due with 8 within budget, previews of 2026-10-15/2026-11-06/2027-01-05, null previews at graduation.
- [x] 7.2 Add `GET /api/study/review-queue` (`study-read`) and `stores/review.js`. Verify with a feature test of the response envelope and summary fields.
- [x] 7.3 Build `pages/review/ReviewPage.vue` at `/app/review`:
  - a question-first card, with grading disabled until Reveal;
  - grade buttons labelled with their preview dates;
  - Space and 1–4 keyboard shortcuts;
  - a blank-page recall prompt with an "Add questions" link for topics without questions;
  - timing sent as `review_seconds`, capped at 3600;
  - a relearn note after Again or Hard;
  - a budget notice with Stop and Continue;
  - a session summary.

  Verify by hand against the review-sessions page scenarios, and that `npm run build` passes.
- [x] 7.4 Build `GradePicker.vue` and use it on the Dashboard and Daily Tasks pages for revision tasks. Learn and practice tasks complete without a grade, and the hard-coded `difficulty_feedback` goes away. Add a "Review" nav item and "Start review" links in `DueTodayBanner`. Verify by hand that checking a revision opens the picker and that no request sends `difficulty_feedback`, and that `npm run build` passes.
- [x] 7.5 Document the review-queue endpoint and the review flow in the API docs, the README and Postman. Verify that the Postman JSON parses.

## 8. Learning-science content, Features page and User Guide

- [x] 8.1 Move the sidebar items from `MainLayout.vue` into `resources/js/config/navigation.js` (name, label, route, icon, `guideSection`), and render the sidebar from it. Verify by hand that the sidebar looks and works the same, and that `npm run build` passes.
- [x] 8.2 Create `resources/js/content/learningScience.js` (design D14, D16) with:
  - the APA references cited by this group's entries, each with a `verified` flag;
  - the techniques retrieval practice, spaced repetition, successive relearning, feedback after retrieval, desirable difficulties and generation, interleaving (with its caveat), realistic planning, consistency over perfection and single-track focus;
  - the algorithms adaptive review scheduler, relearn check, due-review queue, review-time estimate and budget, review-debt detection and never-miss-twice prompt.

  Each worked example must use the dates from the specs. Verify with task 8.3.
- [x] 8.3 Add `scripts/check-learning-content.mjs` (design D15), plus `check:content` and `prebuild` npm scripts. Verify that `npm run check:content` passes, and that it fails when a citation id is deliberately misspelled (then revert the misspelling).
- [x] 8.4 Check each reference against its publication record (authors, year, full title, venue, volume, issue, pages), completing titles the source documents truncate, before setting `verified: true`. Verify that `npm run check:content` reports no unverified references.
- [x] 8.5 Build the shared components `CitationLink.vue`, `ReferenceList.vue`, `TechniqueCard.vue` and `AlgorithmCard.vue`. The algorithm card shows the rule, the worked example, benefits, the evidence badge and citations. Verify by rendering them on the Features page, and that `npm run build` passes.
- [x] 8.6 Build `pages/FeaturesPage.vue` at `/features`: under PublicLayout, with no `requiresGuest`, lazy-loaded. It has the section navigation, the feature overview, techniques, "How StudyTracker decides" and alphabetical references. Make the router's `scrollBehavior` honour `to.hash`. Verify the features-page scenarios by hand, as a guest and logged in.
- [x] 8.7 Refresh `AppTour.vue`:
  - "How it works" and Key Features describe adaptive reviews, recall questions, the review page and the load banner;
  - the fixed-interval explainer is replaced;
  - a link "All features and the science" points to `/features`.

  Also update the About page text. Add "Features" to the PublicLayout desktop and mobile navigation, and fix the mobile "Go To App" link to `/app`. Verify by hand at mobile width.
- [x] 8.8 Create `resources/js/content/userGuide.js` and `pages/guide/UserGuidePage.vue` at `/app/guide`, lazy-loaded, with a "User Guide" navigation item. The guide has:
  - a table of contents and anchors;
  - Getting started, and a daily and weekly routine;
  - a section for every current navigation item (purpose, steps, tips, and the science behind it);
  - its own reference list.

  Verify the user-guide scenarios by hand, and that `npm run check:content` passes.
- [x] 8.9 Add `HelpLink.vue` to every page header, linking to the page's guide section. Add a "New here? Read Getting started" link on the Dashboard when the user has no topics. Verify by hand from each page.
- [x] 8.10 Document the Features page, the User Guide and the content check in `README.md`. Add a rule to `CLAUDE.md`: when behavior changes, update `learningScience.js` and `userGuide.js`. Verify that the README lists both routes.

## 9. Revision schedules: presets and category schedules

- [x] 9.1 Add `GET /api/study/schedule-presets`, served from config (`study-read`). Verify with a feature test that it returns the four presets with their offsets and repeat intervals.
- [x] 9.2 Add `UpsertCategoryScheduleRequest`:
  - `preset_key` or custom `offsets`: 1–10 values, strictly increasing, each 1–3650;
  - `repeat_every_days`: 1–365;
  - `repeat_until`: today or later, and only with a repeat interval;
  - `apply_to_existing_topics`.

  Add `CategoryScheduleApiController`:
  - GET returns the schedule or the name of the fallback;
  - PUT and DELETE carry `deny.demo`;
  - invisible categories return 404 and other users' categories 403;
  - `apply_to_existing_topics` updates only the snapshots of the user's non-archived regular topics in that category.

  Verify feature tests for every API scenario in the revision-schedules spec.
- [x] 9.3 Add the requesting user's `review_schedule` to category listings, eager-loaded. Verify the feature test "Category list with a schedule" and a query-count check.
- [x] 9.4 Build the schedule UI:
  - on the Categories page, a schedule chip ("Default" or a preset summary) and a schedule editor (preset, custom offsets, repeat, until date, apply to existing topics);
  - on the topic create page, the schedule that will apply, shown with `ScheduleSummary`;
  - on the Study Settings page, a preset list with "Use as my default", which fills the template rows.

  Verify by hand, and that `npm run build` passes.
- [x] 9.5 Seed one demo category schedule (Algorithms → `long_horizon`) in `demo:reset`. Document the presets and category-schedule endpoints in the API docs, the README and Postman. Verify that the Postman JSON parses and that `demo:reset` runs.
- [x] 9.6 Add the algorithm "interval presets and exam-date repeat" to the catalog, a feature card for schedules on the Features page, and schedule guidance to the Categories, Topics and Study Settings guide sections. Verify that `npm run check:content` passes.

## 10. Mistake notebook

- [x] 10.1 Implement `StoreMistakeRequest` and `MistakeService::create()`:
  - the required and optional fields from the spec;
  - the parent must be the user's own regular topic; the category must be the user's own or a system one;
  - `logged_on` cannot be in the future;
  - set the title, slug and recall question from the input;
  - take the category and lane from the parent;
  - use the `mistakes` preset, with no Learn task.

  Verify with feature tests: "Log a mistake from a mock test", "Mistake linked to a parent topic", missing cause (422), another user's parent (422), a future date (422), and reviews on 2026-10-12, 2026-10-14 and 2026-10-18 with no Learn task.
- [x] 10.2 Implement the computed mistake state and `GET /api/study/mistakes` with `MistakeResource`: filters for state (default `open`), cause and `parent_topic_id`; newest first; paginated. Verify the tests "Default list" and "Filter by cause".
- [x] 10.3 Implement `PATCH` and `DELETE /api/study/mistakes/{mistake}`:
  - editing the question updates the title and the recall question;
  - a regular topic's id returns 404;
  - another user's entry and demo users return 403.

  Verify the tests "Correct the answer text" and "Regular topic id".
- [x] 10.4 Implement `POST /api/study/mistakes/{mistake}/merge` in a transaction that locks the parent row:
  - only `ready` entries can be merged;
  - append the question unless the parent already has it (case-insensitive);
  - a parent with 10 questions → 422;
  - with no parent, or a deleted one, just close the entry.

  Verify tests for every merge scenario in the mistake-notebook spec.
- [x] 10.5 Build the mistakes UI:
  - `MistakeForm.vue`, `stores/mistakes.js` and `pages/mistakes/MistakesPage.vue`: filters, a log form with a parent picker, the next review date, and Merge or Close on ready entries;
  - a "Mistakes" nav item;
  - "Log a mistake" on the review page, with the parent pre-filled.

  Verify by hand that a logged mistake shows as active with its first review tomorrow, that merging moves it to closed and the parent shows the new question, and that `npm run build` passes.
- [x] 10.6 Seed two demo mistakes, one active and one ready, in `demo:reset`. Document the mistake endpoints in the API docs, the README and Postman. Verify that the Postman JSON parses and that `demo:reset` runs.
- [x] 10.7 Add the technique "learning from errors" and the algorithm "mistake schedule and merge" (with their references) to the catalog. Add a Mistakes feature card and a Mistakes guide section, and add "Log a mistake" to the Review section. Verify that `npm run check:content` passes.

## 11. Weekly planning

- [x] 11.1 Implement `WeeklyPlanService` (design D10):
  - week math: the week start from preferences, and lookup of the latest week containing a date;
  - block generation from gear templates by off days, with Yellow's block A only on the first off day;
  - the score: the counted-blocks rule, partial = 0.5, rounding, `on_track` and the planned total.

  Verify unit tests:
  - 21 / 13 / 7 blocks for green / yellow / red, for the week of 2026-10-11 with off days [5, 6];
  - custom off days [0, 6];
  - the score scenarios: 80% with 7 done, 2 partial, 1 missed and 1 red; a past unmarked block counts as missed; today's unmarked blocks are not counted.
- [x] 11.2 Add two read endpoints:
  - `GET /api/study/weekly-plan`: a null plan with computed week bounds when none exists; otherwise blocks ordered by date then slot, plus score, stats and the success threshold;
  - `GET /api/study/weekly-plan/history`: `weeks` 1–26, default 8.

  Register the history route before any `{week}` route. Verify feature tests "Week without a plan", "Existing plan", "Stats for the current week" and "Eight-week history".
- [x] 11.3 Add the write endpoints for plans:
  - `POST /api/study/weekly-plan`: weekday check, overlap → 422, `generate_blocks`;
  - `PATCH` and `DELETE /api/study/weekly-plan/{week}`;
  - `POST /api/study/weekly-plan/{week}/regenerate`: replaces only future blocks that are still planned.

  All carry `deny.demo`. Verify feature tests for creating a plan, planning next week in advance, wrong weekday, duplicate week, saving the weekly review, downshifting mid-week, and demo users (403).
- [x] 11.4 Add the block endpoints: `POST /weekly-plan/{week}/blocks`, `PATCH /blocks/{block}` and `DELETE /blocks/{block}`. Apply the placement and content validation and the ownership rules (403 for another user's block, 404 for a bad hash). Verify feature tests: add a block, delete a block, pre-decided task, block outside the week (422), unknown lane (422), invalid status (422), red day excluded from the score, another user's block (403).
- [x] 11.5 Build `stores/weeklyPlan.js` and `pages/weekly/WeeklyPlanPage.vue` at `/app/week`:
  - week navigation;
  - a gear selector with hour hints computed from template minutes;
  - focus fields;
  - a score bar with a success-line marker;
  - the overdue count;
  - a seven-day block grid with status toggles and add/edit/remove;
  - reflection fields and "Plan next week";
  - creating a plan for an empty week.

  Add a "Weekly Plan" nav item. Verify by hand against the weekly-planning page scenarios, and that `npm run build` passes.
- [x] 11.6 Build `ThisWeekWidget.vue` on the Dashboard:
  - the gear, and the score so far against the success line;
  - today's blocks with quick done/partial/missed actions;
  - "Next up": the first unmarked block that has a planned task, otherwise a link to the review page;
  - "Plan this week" when there is no plan.

  Verify the three widget scenarios by hand, and that `npm run build` passes.
- [x] 11.7 Seed a current demo week (green, some blocks done or partial) in `demo:reset`. Document the weekly-plan endpoints in the API docs, the README and Postman. Add `StudyWeek` and `StudyBlock` to the hashed-ID model lists in `CLAUDE.md` and the API docs. Verify that the Postman JSON parses and that `demo:reset` runs.
- [x] 11.8 Add the technique "implementation intentions" and the algorithms "weekly gear blocks" and "weekly consistency score" to the catalog. Add a Weekly Plan feature card and a Weekly Plan guide section (gears, blocks, red days, score, weekly review, and the evening-review tip citing Diekelmann & Born 2010 and Mazza et al. 2016). Update the Dashboard section for the this-week widget. Verify that `npm run check:content` passes.

## 12. Integration verification

- [ ] 12.1 Run `vendor/bin/pint --test` and `composer test`. Verify that both pass with all new test suites included.
- [x] 12.2 Run `npm run build`. Verify that it completes without errors or new warnings.
- [ ] 12.3 Run an end-to-end smoke test on a fresh database (`php artisan migrate:fresh --seed`, then `php artisan demo:reset`):
  1. Log in as the demo user and as a normal user.
  2. Create a topic with questions in an `exam_short` category.
  3. Complete the Learn task.
  4. Grade reviews on the review page with Good, Hard and Again.
  5. Check that the topic detail and calendar dates match the scheduler.
  6. Log a mistake and merge it.
  7. Plan a week and mark blocks.

  Verify that each result matches its spec scenario, and record any deviation.
- [x] 12.4 Run `openspec validate adaptive-retrieval-learning-system --strict`. Verify that it reports the change as valid.
- [x] 12.5 Run `npm run check:content`, then read the catalog and guide against the shipped behavior: every worked example must match the scheduler tests, and every menu must have its section. Verify that there are no mismatches.

## Workflow follow-up

- Archive the change with `/opsx:archive` once it has been reviewed and deployed.
- After archiving, confirm that `openspec/specs/` contains the eight new capability specs.
