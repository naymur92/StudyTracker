# Proposal

## Why

Users already design their own weekly blocks: each block has its own minutes and a pre-decided task, shown on the Weekly Plan page and in the dashboard's "This week" widget. But nothing helps them *run* a block. They time it elsewhere, forget breaks, and mark done / partial / missed from memory afterwards, so the weekly score depends on recollection rather than what happened. A timer that starts from the block, stays visible while they work, and records the outcome itself closes that gap.

## What Changes

- **Start a block's timer.** Today's blocks get a Start action on the Weekly Plan page and in the This-week widget. Only blocks dated today can be started, and a user has at most one active timer.
  - A block needs a topic before it starts, except a `review` block, which works through the due-review queue. The start dialog asks for the topic, and for the minutes when the block has none.
  - A `partial` block can be started again on its day. Its time adds up across runs.
- **Breaks inside a block.** A block can have a break pattern: a break of `break_minutes` after every `break_every_minutes` of work. Breaks fall inside the block's planned minutes, so gear hours do not change. The timer notifies when a break starts and when it ends.
- **Mini timer on every app page.** A floating mini timer stays on top of every `/app` page while a timer is active, across navigation and reloads.
  - Selecting it opens a panel with the block, its day, lane, planned task, topic, time left, the current phase and the next break.
  - The panel has pause/resume, stop and discard controls.
  - Where the browser supports it (Chrome and Edge on desktop), a "Pop out" button opens the timer in a small always-on-top window over other apps.
- **Outcome recorded automatically.**
  - When the block's time runs out, the block becomes `done`. The app plays a sound, shows a notification, and opens the block's topic page with a short "What did you learn?" wrap-up. A review block opens the Review page instead.
  - The wrap-up saves a practice log with the timer's minutes. It can be skipped.
  - Stopping early marks the block `partial`. A run shorter than one minute is discarded instead.
  - Discarding a run leaves the block's status unchanged.
- **Server-side timer sessions.** Each run is stored as a session, so a timer survives reloads, closed tabs and other devices. A timer whose time ran out while no page was open is completed on the next read. The browser keeps a localStorage copy only for instant display.
- **Block payload.** Block responses gain `actual_minutes`, `break_every_minutes`, `break_minutes` and a `timer` object (state, time used, time left).
- **Running blocks are protected.** While a block's timer is active:
  - regenerating the week does not replace it;
  - its date, minutes, status and break pattern cannot be changed;
  - it cannot be deleted.
- **Block editor.** The Weekly Plan page gains an edit dialog for a block: task, minutes, topic, break pattern, slot, lane and note. Today, only the planned task can be edited on the page.
- No **BREAKING** changes. Existing fields and endpoints keep their behaviour, and blocks without a break pattern or sessions look as they do today.

## Capabilities

### New Capabilities

- `study-block-timer`: the block timer, covering:
  - starting, pausing, resuming, stopping and discarding a block's timer;
  - break phases and alerts;
  - automatic done/partial outcomes;
  - the always-visible mini timer and pop-out window;
  - the finish wrap-up;
  - timer sessions and their place in data deletion.

### Modified Capabilities

- `weekly-planning`:
  - block content fields gain the break pattern;
  - regeneration keeps blocks whose timer is active;
  - the block grid gains Start, a running indicator, recorded minutes and a block editor;
  - the This-week widget gains Start and a running indicator.

## Impact

- **Database.**
  - A new `study_block_sessions` table, one row per timer run.
  - `study_blocks` gains `break_every_minutes` and `break_minutes`.
- **Backend.**
  - A new `StudyBlockSession` model (hashed IDs) and a `BlockTimerService` that starts, pauses, resumes, stops, discards and settles sessions.
  - A `BlockTimerApiController` with these routes:
    - `GET /api/study/timer`
    - `POST /api/study/blocks/{block}/timer/{start|pause|resume|stop}`
    - `DELETE /api/study/blocks/{block}/timer`

    The routes use the study rate limiters; the write routes also use `deny.demo`.
  - `StudyBlockRequest` validates the break pattern and locks running blocks.
  - `WeeklyPlanService::regenerate()` skips running blocks.
  - `StudyBlockResource` gains the new fields.
  - `DataCategoryRegistry` registers the new table under `weekly_plans`.
- **Frontend.**
  - A persisted `blockTimer` Pinia store.
  - A timer host mounted in `MainLayout`, with the mini timer, panel, alerts (sound, Notification API, tab title) and the Document Picture-in-Picture pop-out.
  - A start dialog with a topic picker.
  - The block editor and Start actions in `WeeklyPlanPage.vue` and `ThisWeekWidget.vue`.
  - The wrap-up panel on the topic page.
- **Content.** The User Guide sections for Weekly Plan, Dashboard and Topics, and the `weekly-score` catalog entry: the timer now records outcomes.
- **Docs.** `API-DOCUMENTATION.md`, the README and the Postman collection.
- **Tests.** Feature tests for the timer endpoints, outcomes, settling, locks, regeneration and data deletion coverage.
