# Tasks

## 1. Data model

- [x] 1.1 Add the block break pattern (design D5):
  - a migration adding nullable `break_every_minutes` and `break_minutes` (`unsignedSmallInteger`) to `study_blocks`;
  - `StudyBlock` fillable fields;
  - `StudyBlockRequest` rules: 10–120 and 1–30, each `required_with` the other;
  - both fields in `StudyBlockResource`.

  Verify with new `WeeklyPlanTest` cases:
  - setting 50/10 on a 150-minute block saves it and keeps 150 minutes;
  - `break_minutes` alone gets a 422 on `break_every_minutes`;
  - generated blocks have null patterns.
- [x] 1.2 Create `study_block_sessions` (design D1):
  - the FKs, the index on `study_block_id`, and the unique nullable `active_user_id`;
  - a `StudyBlockSession` model with `HashesIds` and datetime casts;
  - `StudyBlock::sessions()` and `StudyBlock::activeSession()`.

  Verify that `php artisan migrate` and `php artisan migrate:rollback --step=2` round-trip against MySQL locally.
- [x] 1.3 Register the new table in `DataCategoryRegistry` (design D9):
  - the required edge to `study_blocks`;
  - `DELETE_ORDER` before `study_blocks`;
  - the "Block timer runs" record label;
  - the `weekly_plans` note.

  Verify that `RegistryCoversSchemaTest` and `DataCategoryRegistryTest` pass.

## 2. Timer API

- [x] 2.1 Add the pure `Scheduling\BlockTimerMath` (design D2): used seconds with pauses, `phaseAt`, `nextBreakAt` and `runOutAt`. Verify with a `BlockTimerMathTest` unit test using the spec vectors:
  - 150 min with 50/10 gives five phases;
  - 120 min with 50/10 gives one break;
  - 90 min with 50/10 at 52 minutes gives a break with 480 s left;
  - a block without a pattern is always `work`;
  - a 10-minute pause leaves the time used unchanged.
- [x] 2.2 Implement `start`, `settle` and the lookup:
  - `BlockTimerService::start()`, with the user-row lock, the optional `topic_id` and `planned_minutes`, and a 409 for a second active run, including a unique-index violation;
  - `BlockTimerService::settle()`;
  - `BlockTimerResource`;
  - `BlockTimerApiController::show()` and `start()`;
  - the routes `GET /api/study/timer` (`study-read`) and `POST /api/study/blocks/{block}/timer/start` (`study-write`, `deny.demo`).

  Verify with a new `tests/Feature/Study/BlockTimerTest.php` covering every scenario of these spec requirements:
  - Start a block's timer (today's block, another day, done block, demo user);
  - Topic and minutes before starting;
  - One active timer per user;
  - Active timer lookup: no timer;
  - Timer access control: another user's block, a malformed ID.
- [x] 2.3 Implement pause and resume, resuming only on the block's day. Verify with `BlockTimerTest` cases, using `Carbon::setTestNow`:
  - a pause followed by a resume keeps the time left;
  - a timer paused from 10:20 to 10:30 shows 35 used and 55 left at 10:45;
  - resuming on the next day gets a 422, and the timer stays paused;
  - pausing a paused timer gets a 422.
- [x] 2.4 Implement stop and discard (design D4):
  - a run under 60 s is discarded;
  - within 30 s of the end, the block becomes `done` with its full planned time;
  - otherwise the block becomes `partial`;
  - `DELETE /api/study/blocks/{block}/timer` removes the active run.

  Verify with `BlockTimerTest` cases:
  - stopping after 40 min gives `partial` with 40 minutes;
  - stopping with 10 s left gives `done` with 90 minutes;
  - stopping after 20 s keeps the block `planned`, with no run;
  - discarding a second run keeps the block `partial` with 40 minutes;
  - continuing a partial block for 50 more minutes gives `done` with 90;
  - stopping without a timer gets a 422;
  - stopping another user's block gets a 403.
- [x] 2.5 Call `settle()` from the weekly-plan `show` and `history` actions and before every timer action (design D3). Verify with `BlockTimerTest` cases:
  - a timer is `done` once its 90 minutes have run;
  - a 20-minute block started at 09:00 and read at 11:00 is `done`, ended at 09:20;
  - `GET /timer` returns `recent` with `finished` for a run that ended while away;
  - a paused timer never runs out.
- [x] 2.6 Add `actual_minutes` and `timer` to `StudyBlockResource`, eager-loaded in `WeeklyPlanService::orderedBlocks()` (design D6). Verify with tests:
  - the weekly plan shows a `timer` only on the running block;
  - the timer object at 52 minutes of a 90-minute 50/10 block shows 3120 used and 2280 left, with phase `break` and 480 seconds left in it;
  - with 7 blocks, the plan fetch makes no per-block queries for runs, checked by counting queries with `DB::listen`.
- [x] 2.7 Lock running blocks and update regeneration (design D5):
  - the `StudyBlockRequest::after()` lock;
  - a 422 from `destroyBlock` for a block with an active run;
  - `regenerate()` skips blocks with an active run.

  Verify with `WeeklyPlanTest` cases:
  - setting a running block to `done` gets a 422 on `status`;
  - deleting a paused block gets a 422;
  - editing a running block's task is saved;
  - regenerating on Tuesday keeps the running morning block and replaces the other planned blocks.
- [x] 2.8 Add the data deletion pre-step: when a request includes `weekly_plans`, settle, then end any active run as `stopped` (design D9). Extend `SeedsDeletableData` with runs.

  Verify with `ExecuteDeletionTest` and `RestoreTest` cases:
  - for 2 plans, 9 blocks and 3 runs, everything is deleted and the archive lists the 3 runs;
  - a restore brings the runs back with the same `actual_minutes`;
  - a run that was active is archived as ended.
- [x] 2.9 Document the six timer routes, the timer object, `recent`, the new block fields and the running-block locks in `API-DOCUMENTATION.md`, the README endpoint list and the Postman collection. Verify that `python3 -m json.tool StudyTracker-API.postman_collection.json` parses, and that the documented response shapes match the 2.2–2.6 test responses.

## 3. SPA timer core

- [x] 3.1 Add `resources/js/components/timer/timerMath.js`, mirroring `BlockTimerMath`. Add `scripts/check-timer-math.mjs` with the same vectors as 2.1, and an npm `check:timer` script chained into `prebuild` after `check:content`. Verify that `npm run check:timer` passes and fails when a vector is altered.
- [x] 3.2 Add the persisted `stores/blockTimer.js` Pinia store (design D7):
  - the state is `timer`, `recent`, `serverOffsetMs`, `syncedAt`, `firedAlerts` and `wrapup`;
  - the actions are `sync`, `start`, `pause`, `resume`, `stop` and `discard`;
  - the store is reset on `authStore.logout()`.

  Verify by hand:
  - after starting a block through the API and reloading, the store holds the timer;
  - after logout, the store's localStorage key is cleared.
- [x] 3.3 Add `timerAlerts.js`:
  - a Web Audio chime;
  - a `Notification` with `tag`;
  - an in-app toast through `helpers/alerts`;
  - once-only firing through `navigator.locks` and `firedAlerts`, with a localStorage fallback;
  - the permission request and audio unlock inside the Start click.

  Verify by hand:
  - with two tabs open, one break alert gives one notification and one sound;
  - with notifications denied, the toast and the sound still occur.
- [x] 3.4 Add `BlockTimerHost.vue`, mounted in `MainLayout.vue`:
  - a 1-second display tick only while the page is visible;
  - single boundary timeouts;
  - sync on load, on visibility, at boundaries and every 2 minutes;
  - a `BroadcastChannel` re-sync;
  - the tab title;
  - at run-out, `GET /timer` and the finish flow from `recent`.

  Verify by hand with a 12-minute block and a 10/1 pattern:
  - the break alerts fire at 10:00 and 11:00;
  - a tab left hidden for the whole block still records `done` and alerts within about a minute of the end;
  - `npm run build` passes.

## 4. SPA screens and guide

- [x] 4.1 Add `TimerFace.vue`, `MiniTimer.vue` and `TimerPanel.vue` (design D8):
  - the panel shows the slot, day, lane, task, topic link, used and left time, progress, phase and next break;
  - it has pause or resume, and stop and discard with confirmation.

  Verify by hand:
  - the chip stays visible while moving between Dashboard, Topics and Review, and after a reload;
  - the panel shows the "What am I doing?" scenario's details;
  - stop and discard ask for confirmation.
- [x] 4.2 Add the Document Picture-in-Picture pop-out:
  - copy the page's styles;
  - mount `createApp(PopOutTimer).use(pinia)` with the shared Pinia;
  - unmount on `pagehide`;
  - show the button only when the browser supports it.

  Verify by hand:
  - in desktop Chrome, the window stays above another application and pause works from it;
  - in Firefox, no "Pop out" button appears.
- [x] 4.3 Add `StartBlockDialog.vue`, with the topic picker listing the block's lane first and a minutes field. Add Start actions and running/paused badges to `WeeklyPlanPage.vue` and `ThisWeekWidget.vue`:
  - Start appears on today's eligible blocks only;
  - Start is disabled while another timer is active;
  - Start is disabled for demo users;
  - the widget's status buttons are disabled on the running row.

  Verify by hand:
  - Start on a `major` block without a topic opens the dialog;
  - a review block starts directly;
  - Wednesday's blocks have no Start on Tuesday;
  - the demo user sees Start disabled with its hint.
- [x] 4.4 Add `BlockEditorDialog.vue` and the Edit button on block cards:
  - the fields are task, minutes, topic, break preset or custom, slot, lane and note;
  - locked fields are disabled while the block's timer is active;
  - cards show "used / planned min" once a block has recorded time.

  Verify by hand:
  - editing Saturday's Block A to 120 minutes, "Binary trees" and 50 + 10 shows on its card;
  - a stopped 90-minute block shows "40 / 90 min".
- [x] 4.5 Add the finish flow in the host:
  - a finish toast with a 5-second "Stay here" countdown, then navigation to `/app/topics/{topic}?wrapup={block}` or `/app/review`;
  - a "Log what you learned" link after an early stop;
  - a "Block finished" notice, without navigation, when the run ended while the app was closed.

  Verify by hand for all three paths, and that "Stay here" keeps the current page.
- [x] 4.6 Add `WrapUpPanel.vue` on `topics/DetailPage.vue`, shown when `?wrapup` matches `blockTimer.wrapup`. Save creates a practice log through the `practiceLogs` store with the block's topic, date and `actual_minutes`, the chosen type (default Other) and the details. Skip creates nothing. Both clear the query. Verify by hand:
  - after saving, the log appears in the topic's Practice History with the block's date and minutes;
  - after skipping, no log is added.
- [x] 4.7 Update the content (design D10):
  - the User Guide's Weekly Plan, Dashboard and Topics sections;
  - the `weekly-score` rule in `learningScience.js`;
  - the weekly-plan card in `features.js`.

  Verify that `npm run check:content` passes and that the guide's Weekly Plan section describes Start, breaks and automatic outcomes.

## 5. Integration

- [x] 5.1 Run `composer test` (against MySQL locally), `vendor/bin/pint --test` on the changed PHP files and `npm run build`. Verify that all pass.
- [x] 5.2 Walk the full flow by hand on a 12-minute block with a 10/1 pattern:
  - start from the dashboard without a topic, then pick one in the dialog;
  - open other pages and see the mini timer;
  - get the break-start and break-end alerts;
  - when the block finishes, the topic wrap-up opens and saves a practice log;
  - stop a second block early and see `partial`;
  - close the browser mid-block, reopen it after the end, and see the "Block finished" notice.

  Verify that the weekly score counts the done and partial blocks.
- [x] 5.3 Run `openspec validate add-study-block-timer --strict`. Verify that it reports valid.

## Workflow follow-up

- Archive the change with `/opsx:archive` after implementation and review. The archive creates the `study-block-timer` main spec and applies the `weekly-planning` delta.
