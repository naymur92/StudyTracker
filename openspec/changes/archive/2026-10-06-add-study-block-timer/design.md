# Design

## Context

For motivation, see proposal.md → Why. For the behaviour, see the two delta specs.

What exists today:
- **Blocks.** `study_blocks` rows belong to a `study_weeks` plan. The block fields are `block_date`, `slot`, `lane`, `planned_task`, `planned_minutes`, `status` and `note`, plus optional `category_id` and `topic_id`. There is no notion of time actually spent, and no break pattern.
- **Outcome rule.** `StudyBlockRequest::after()` already stops a block dated after today from carrying `done`, `partial` or `missed`. Starting only today's blocks (proposal) keeps the timer inside that rule.
- **Readers of blocks.** Only `WeeklyPlanService` and `WeeklyPlanApiController` read blocks. Reports and the admin panel do not. The score is computed on read.
- **Block editing in the UI.** `WeeklyPlanPage.vue` can edit only the planned task inline. The API accepts `topic_id`, `planned_minutes`, `slot`, `lane` and `note`, but no page sends them. `ThisWeekWidget.vue` has quick done/partial/missed buttons.
- **SPA plumbing.**
  - `MainLayout.vue` wraps every `/app` page, so it is the one place to mount UI that must survive navigation.
  - Pinia is used with `pinia-plugin-persistedstate`, as the `auth` store does with `persist.paths`.
  - The app does not use the Notification API, Web Audio or Picture-in-Picture anywhere yet.
- **Data deletion.** `DataCategoryRegistry` must list every table with a `user_id`; `RegistryCoversSchemaTest` fails otherwise. Child rows follow `EDGES`, and `ExecuteDataDeletionService` runs inside one transaction after locking the user row.
- **Clock.** The application timezone is `Asia/Dhaka`. Server "today" decides which blocks can start, as it already does for outcomes.

## Goals / Non-Goals

**Goals:**
- One source of truth for a timer that survives reloads, closed tabs, several tabs and several devices.
- Exact outcomes even when no page is open at the end of a block.
- Alerts that are timely in a visible tab and at most about a minute late in a long-hidden tab, never duplicated.
- No new runtime dependencies.

**Non-Goals:**
- A free-standing stopwatch, or timing outside weekly-plan blocks. Review sessions already record `review_seconds` per card.
- Focus-minute totals in weekly stats, reports or history. The data allows it later.
- Default break patterns in the gear templates. Generated blocks have none, and users add them per block.
- Push notifications, a service worker or alerts with the browser closed. The outcome is still recorded, and the next visit shows "Block finished".
- Extending a running block ("+10 min"), or editing recorded time by hand.

## Decisions

### D1. A run table on the server; localStorage only mirrors it

A new table `study_block_sessions` stores one row per timer run:

| Column | Notes |
| --- | --- |
| `id` | Hashed in the API (`HashesIds`) as `session_id`. |
| `user_id` | FK to `users`, cascade on delete. |
| `study_block_id` | FK to `study_blocks`, cascade on delete. Indexed. |
| `active_user_id` | Nullable and **unique**. It equals `user_id` while the run is running or paused, and is null once it has ended. |
| `started_at` | When the run started. |
| `resumed_at` | Nullable. The start of the current running stretch; null while paused or ended. |
| `used_seconds` | Unsigned int, default 0. The total of the stretches already closed. |
| `ended_at` | Nullable. When the run ended. |
| `end_reason` | Nullable `string(10)`: `finished` or `stopped`. |
| `timestamps` | |

A run is in one of three states:
- **running:** `active_user_id` is set and `resumed_at` is set;
- **paused:** `active_user_id` is set and `resumed_at` is null;
- **ended:** `ended_at` is set.

A run's live time used is `used_seconds + (now − resumed_at)` while it runs. A block's time used is the sum of its ended runs plus its active run.

**Why a server table.** The outcome is a server write anyway (the block status). The user also asked for a timer that keeps going while they move around the app. A server row survives the things localStorage does not:
- a closed browser;
- cleared storage;
- a second device;
- two tabs racing to write the outcome.

The browser keeps a persisted Pinia copy (D7), so a reload paints immediately.

Alternatives considered:
- **localStorage only** (the user's suggestion). The block would stay `planned` if the browser closed before the end, and two tabs or two devices could not agree. It was rejected for those reasons, but kept as the instant-display cache.
- **Timer columns on `study_blocks`.** A `partial` block can be started again, and discarding must undo only the latest run. Per-run rows make both trivial, while block columns would need a "time before this run" field and still lose history.
- **`active_user_id` versus a lock only.** MySQL has no partial unique index. A nullable unique column is the portable way to make "one active run per user" a database guarantee. The service still locks the user row (D4), and the index is the backstop.

### D2. Pure timer arithmetic, mirrored in JavaScript

`App\Services\StudyTracker\Scheduling\BlockTimerMath` is a pure class, like `RecallScheduler`. It has no database access and covers:
- `usedSeconds(session, now)`;
- `phaseAt(usedSeconds, plannedSeconds, breakEvery, breakMinutes)`, which returns the phase and the seconds left in it;
- `nextBreakAt(...)`;
- `runOutAt(session, priorSeconds, plannedSeconds)`.

A break is taken only if it ends before the block's end (spec: Break phases).

`resources/js/components/timer/timerMath.js` implements the same functions for the live display and the alert scheduling. Both are tested with the spec's scenarios as shared vectors: 150 min with 50/10, 120 min with 50/10, and 90 min with 50/10 at 52 minutes. The JS vectors are checked with a small `node` assertion script, run by hand or next to `check:content`. The repo has no JS test runner, and this change does not add one.

### D3. Settle lazily, not on a schedule

`BlockTimerService::settle(User)` finds the user's running run. If the time left is ≤ 0 at `now`, it ends the run at `runOutAt`:
- `used_seconds` is set so the block's total equals its planned minutes;
- `end_reason` is `finished`;
- the block's status is set to `done`.

It is called from:
- `GET /api/study/timer`;
- the weekly-plan `show` and `history` actions;
- the start of every timer action;
- the data deletion pre-step (D9).

Paused runs never run out.

Alternative considered: a `schedule:run` command every minute. Block state is only ever seen through these reads, so the lazy settle is exact at no cost, and it needs no cron on new environments. The settle is one indexed query on `active_user_id`.

### D4. Service operations and concurrency

`BlockTimerService` provides `start`, `pause`, `resume`, `stop`, `discard` and `settle`. Each runs in a transaction that first locks the user row with `User::whereKey()->lockForUpdate()`. Deletion uses the same pattern, so two tabs clicking Start serialize.

- **`start`.**
  - Validates the rules in the spec: today, status, minutes, topic unless the lane is `review`, and time left.
  - Saves an optional `topic_id` and `planned_minutes` first.
  - Rejects a second active run with 409.
  - A unique-index violation on `active_user_id` is also mapped to 409.
- **`stop`.**
  - A run under 60 s is deleted.
  - Otherwise the run is ended with `end_reason = stopped`.
  - If the block's total is within 30 s of the planned minutes, the run is topped up to the planned total, marked `finished`, and the block becomes `done`. Otherwise the block becomes `partial`.
- **`discard`** deletes the active run. The block status is not touched, because a run never changes the status until it ends.

Validation errors use `ValidationException` (422, field keys as in the spec). The 409 uses `abort(409, …)`, and `Handler` already renders `HttpException` in API format. A request from a second tab after the run has ended gets 422 "no active timer". The client treats that as "already ended" and re-syncs.

The outcome writes go straight through the service, not through `StudyBlockRequest`. They cannot break the future-outcome rule, since the block is dated today or earlier when the run ends.

### D5. Block locks and regeneration

- **`StudyBlockRequest::after()`** gains a check: when the route block has an active run, any of `block_date`, `planned_minutes`, `status`, `break_every_minutes` or `break_minutes` in the request adds a 422 error on that field ("Stop the block's timer first.").
- **`destroyBlock`** returns 422 for a block with an active run.
- **`WeeklyPlanService::regenerate()`** adds `whereDoesntHave('activeSession')` to its delete query. As with blocks already marked, the new template can add a block for the same slot on the same day. This matches today's behaviour for marked blocks.
- **Break pattern validation.** `break_every_minutes` must be `nullable|integer|between:10,120|required_with:break_minutes`, and `break_minutes` must be `nullable|integer|between:1,30|required_with:break_every_minutes`. A new migration adds both as nullable `unsignedSmallInteger` columns on `study_blocks`.

### D6. API surface and payloads

These routes are in the existing `study` group:

| Route | Limiter | Demo users |
| --- | --- | --- |
| `GET /timer` | `study-read` | allowed |
| `POST /blocks/{block}/timer/start` | `study-write` | `deny.demo` |
| `POST /blocks/{block}/timer/pause` | `study-write` | `deny.demo` |
| `POST /blocks/{block}/timer/resume` | `study-write` | `deny.demo` |
| `POST /blocks/{block}/timer/stop` | `study-write` | `deny.demo` |
| `DELETE /blocks/{block}/timer` | `study-write` | `deny.demo` |

The controller is `BlockTimerApiController`, and every action returns the lookup shape `{timer, recent, server_time}` through `jsonResponse()`. The timer object is described in the spec. A new `BlockTimerResource` builds it, embedding `StudyBlockResource`.

`StudyBlockResource` gains:
- `break_every_minutes` and `break_minutes`;
- `actual_minutes`, from `withSum` over ended runs, rounded;
- `timer`, from the eager-loaded `activeSession`, or null.

`orderedBlocks()` eager-loads both to avoid N+1 queries. `StudyBlock` gains the `sessions()` and `activeSession()` relations.

`recent` is the latest ended run with `ended_at` ≥ now − 12 h. The client uses it to:
- reconcile after a run ended elsewhere;
- fill the wrap-up.

### D7. SPA timer core

**Store.** `stores/blockTimer.js` is a Pinia store, persisted under `paths: ['timer', 'recent', 'serverOffsetMs', 'syncedAt', 'firedAlerts', 'wrapup']`.
- Actions call the API and replace the state from the response.
- `serverOffsetMs = server_time − Date.now()` at each response.
- `firedAlerts` keeps keys for the last few runs only.
- Logout resets the store.

**Host.** `components/timer/BlockTimerHost.vue` is mounted once in `MainLayout.vue`, next to `<router-view>`. It owns time and side effects:
- **Display tick.** A 1-second `setInterval` runs only while the document is visible. The displayed values are always recomputed from `syncedAt`, `serverOffsetMs` and the timer object with `timerMath.js`, never by counting ticks.
- **Boundary timers.** One `setTimeout` per upcoming boundary (break start, break end, run-out), recomputed after each sync. These are single timeouts rather than a chained ticker, so Chrome's intensive throttling of hidden tabs (which targets timer chains) does not hold them for minutes. A late wake-up still computes from wall-clock time.
- **Sync.** `GET /timer` runs:
  - on app load;
  - on `visibilitychange` to visible;
  - at each boundary;
  - every 2 minutes while a timer is active, which picks up pauses from another device.
- **Run-out.** At run-out the host calls `GET /timer` rather than `stop`. The server settles and returns `recent.end_reason = finished`, so the server alone decides the outcome. If the server still shows a few seconds left (clock skew), the host reschedules.
- **Tab title.** While running, the title is `▶ 41:20 · Morning deep block`. The previous title is restored when the timer ends.
- **Several tabs.** A `BroadcastChannel('study-timer')` tells the other tabs to re-sync after any action.

**Alerts (`timerAlerts.js`).** Each alert has a key, `${session_id}:${event}`, where `event` is `break-start-N`, `break-end-N` or `finished`.
- The alert runs inside `navigator.locks.request('study-timer-alert', { ifAvailable: true })` with a check-and-set on `firedAlerts`. Exactly one tab fires it, and a reload does not repeat it.
- Where Web Locks is missing, the localStorage check alone is used.
- Each alert:
  - shows an in-app toast through the existing `helpers/alerts` / SweetAlert2 toast;
  - plays a short Web Audio chime, so no audio asset is needed;
  - shows a `Notification` with `tag` set to the key, when permission is granted.
- Notification permission is requested, and the `AudioContext` created and resumed, inside the Start click. A one-time `pointerdown` listener after a reload re-unlocks audio.

### D8. SPA screens

- **`MiniTimer.vue`** is a fixed chip at the bottom right, above page content and below SweetAlert modals, in the z-index range of the existing sidebar. It shows the slot label, time left and the phase colour. Selecting it toggles **`TimerPanel.vue`**, which has the details and controls from the spec. Both render from one presentational `TimerFace.vue`, so the pop-out reuses it.
- **Pop-out.** `window.documentPictureInPicture.requestWindow({ width: 320, height: 200 })`:
  - copies the page's stylesheet `<link>` and `<style>` nodes into the new document;
  - mounts a second small Vue app, `createApp(PopOutTimer).use(pinia)`, into its body. Sharing the **same Pinia instance** keeps it reactive with no messaging;
  - unmounts on the window's `pagehide`.

  The button exists only when `'documentPictureInPicture' in window`.

  Alternative considered: video Picture-in-Picture with a canvas-drawn timer. It works in more browsers but offers no buttons, so it was rejected.
- **`StartBlockDialog.vue`** opens when Start is selected on a block missing a topic (non-review lane) or minutes. Its topic picker loads `GET /api/study/topics?status=active&per_page=100&search=`. It lists topics whose `lane` matches the block's lane first: a `major` block shows `major` topics, then the rest. Confirming calls `start` with `topic_id` and `planned_minutes`. Blocks that already have both start without the dialog.
- **`BlockEditorDialog.vue`** on `WeeklyPlanPage.vue`:
  - reuses the same topic picker;
  - offers break presets (None, 25 + 5, 50 + 10, Custom);
  - disables the locked fields while `block.timer` is set.

  Block cards gain:
  - an Edit button;
  - Start on today's eligible blocks;
  - a running or paused badge with time left;
  - "used / planned min" once `actual_minutes > 0`.
- **`ThisWeekWidget.vue`** gains Start and the running badge on today's rows. Its done/partial/missed buttons are disabled on the running row, because the server would reject them.
- **Finish navigation.**
  - On a live run-out, the host shows the finish toast with a 5-second "Opening wrap-up… / Stay here" countdown, then `router.push`:
    - `/app/topics/{topic_id}?wrapup={block_id}`;
    - `/app/review` for a review block.
  - On load-time reconciliation (finished while away), it shows the notice only.
  - The wrap-up data (block id, topic id, date, minutes, slot, task) is stored in `blockTimer.wrapup` from `recent`.
- **Wrap-up.** `topics/DetailPage.vue` shows a `WrapUpPanel.vue` when `route.query.wrapup` matches `blockTimer.wrapup.block_id`. Saving calls the existing `practiceLogs` store with:
  - `practice_type` (default `other`);
  - `details`;
  - `practiced_on = block_date`;
  - `duration_minutes = actual_minutes`.

  Save or Skip clears `wrapup` and removes the query. On another device without the store data, the panel is not shown. This is acceptable: the practice log form is still one click away.
- **Demo users.** Start is rendered disabled with the title "Not available in the demo" when `authStore.isDemoUser`.

### D9. Data deletion

`DataCategoryRegistry` changes:
- `EDGES` gains `['study_block_sessions', 'study_block_id', 'study_blocks', true]`;
- `DELETE_ORDER` gains `study_block_sessions` before `study_blocks`;
- `RECORD_LABELS` gains "Block timer runs";
- the `weekly_plans` notes gain "Timer runs of these blocks are deleted too."

Topics deletion is unaffected: blocks keep their runs and lose only the topic link.

In `ExecuteDataDeletionService::run()`, inside the transaction and after the user lock, when the request includes `weekly_plans`:
1. call `BlockTimerService::settle()`;
2. end any active run as `stopped` at now. `active_user_id` becomes null, and the block status is left alone, since the block is about to be deleted.

The archive then holds only ended runs, and restore inserts them verbatim with no unique-key clash.

### D10. Content and docs

- **User Guide (`userGuide.js`).**
  - Weekly Plan: the block editor, break patterns, Start (today only), and the outcome from the timer.
  - Dashboard: Start from This week, the mini timer and panel, and pop-out.
  - Topics: the wrap-up.
- **Catalog (`learningScience.js`).** The `weekly-score` entry's rule gains "A block's timer marks it done when its time runs out, and partial when stopped early". No new references are added.
- **`features.js`.** The weekly-plan card mentions the block timer.
- **Docs.** `API-DOCUMENTATION.md`, the README endpoint list and feature bullet, and Postman examples for the six routes.

## Risks / Trade-offs

- **Hidden-tab throttling delays alerts.** → Boundary alerts use single timeouts (D7). Times are always computed from timestamps, and the outcome is decided on the server. The worst case is an alert about a minute late in a long-hidden tab.
- **Sound is blocked by the autoplay policy after a reload with no click yet.** → Audio is unlocked by the Start click and re-unlocked on the first `pointerdown`. Notifications and the in-app toast still fire.
- **Auto-navigation on finish could drop unsaved typing.** → There is a 5-second "Stay here" countdown (spec). The current page is never left without it.
- **The client clock or timezone differs from the server.** → `serverOffsetMs` corrects the countdown. Start eligibility in the UI uses the browser's date, as the outcome buttons already do. The server re-validates against its own "today" and returns a clear 422 message.
- **Another device paused the timer, and this device shows it running until the next sync.** → Sync on visibility and every 2 minutes. Actions on stale state return 422 and trigger a re-sync.
- **Regenerating while a block runs can leave two blocks for the same slot on that day.** → This matches the existing behaviour for marked blocks. The user can delete the extra block.
- **Document Picture-in-Picture is Chromium-desktop only.** → The feature is detected, and the button is hidden elsewhere. The in-app mini timer is the baseline everywhere.
- **Demo users cannot try the timer.** → This follows the project rule that every mutating route carries `deny.demo`. The UI explains why.

## Migration Plan

1. Migration A adds nullable `break_every_minutes` and `break_minutes` to `study_blocks`. Migration B creates `study_block_sessions` with its FKs, the index on `study_block_id`, and the unique index on `active_user_id`. Both are additive, and their `down()` drops what they add.
2. Deploy code and migrations together. `deploy/deploy.sh` already runs `migrate`, and old SPA bundles ignore the new fields.
3. Rollback: revert the commit and roll back the two migrations. This loses recorded runs and break patterns only; block statuses written by timers stay as ordinary statuses.

## Open Questions

- Should weekly stats or reports show the minutes recorded by timers, beside planned minutes? This is deferred. The data model supports it without changes.
