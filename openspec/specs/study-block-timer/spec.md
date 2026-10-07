# study-block-timer Specification

## Purpose
Run a weekly-plan block with a timer that starts from the block, stays visible on every app page, alerts at breaks and at the end, and records the block's outcome from the time actually spent.

## Requirements

### Requirement: Start a block's timer
`POST /api/study/blocks/{block}/timer/start` SHALL start a timer on a block that:
- is dated today in the application timezone;
- has status `planned` or `partial`;
- has time left.

The response SHALL return the running timer. Any other block SHALL be rejected with a 422 response. The route uses the study write rate limiter and responds 403 for demo users.

#### Scenario: Start today's morning block
- **WHEN** on Tuesday 2026-10-13 a user starts that day's 90-minute morning block, which has a topic
- **THEN** the API returns a running timer with 90 minutes left, and the block's status stays `planned`

#### Scenario: Block on another day
- **WHEN** on Tuesday a user starts Wednesday's block or Monday's block
- **THEN** the API responds 422 with a validation error on `block_date`, and no timer starts

#### Scenario: Block already done
- **WHEN** a user starts today's block that has status `done`
- **THEN** the API responds 422 with a validation error on `status`

#### Scenario: Demo user
- **WHEN** a demo user starts a block's timer
- **THEN** the API responds 403 and no timer starts

### Requirement: Topic and minutes before starting
A block SHALL have a topic before its timer starts, unless its lane is `review`. A block SHALL also have planned minutes. The start request MAY include `topic_id` and `planned_minutes` (5–480), which SHALL be saved on the block first. A missing topic or missing minutes SHALL be rejected with a 422 response on that field.

#### Scenario: No topic set
- **WHEN** a user starts a `major` block without a topic and sends no `topic_id`
- **THEN** the API responds 422 with a validation error on `topic_id`, and no timer starts

#### Scenario: Topic chosen at start
- **WHEN** a user starts a `major` block without a topic and sends the ID of one of their topics
- **THEN** the block's topic is saved and its timer starts

#### Scenario: Review block without a topic
- **WHEN** a user starts a `review` block that has no topic
- **THEN** the timer starts

#### Scenario: Block without minutes
- **WHEN** a user starts a block whose planned minutes are empty, and sends no `planned_minutes`
- **THEN** the API responds 422 with a validation error on `planned_minutes`

### Requirement: One active timer per user
A user SHALL have at most one active timer, running or paused. Starting a block while another block's timer is active SHALL be rejected with a 409 response that names the active block. Nothing SHALL change.

#### Scenario: Second block while one is running
- **WHEN** a user's morning block timer is running and they start today's review block
- **THEN** the API responds 409, naming the morning block, and the review block has no timer

### Requirement: Time used and time left
A block's time used SHALL be the time of all its timer runs, excluding paused time. Time left SHALL be its planned minutes minus the time used. A run that continues past midnight SHALL keep counting.

#### Scenario: Time with a pause
- **WHEN** a 90-minute block's timer started at 10:00, was paused from 10:20 to 10:30 and is read at 10:45
- **THEN** the timer shows 35 minutes used and 55 minutes left

### Requirement: Break phases
A block with a break pattern SHALL divide its time into phases, counted on the time used:
- `break_every_minutes` of work, then `break_minutes` of break, repeated;
- a break that would end at or after the block's end is not taken.

Breaks count as block time. The timer SHALL report the current `phase` (`work` or `break`) and the seconds left in it.

#### Scenario: Block A with breaks
- **WHEN** a 150-minute block has a pattern of 50 minutes of work and 10 minutes of break
- **THEN** its phases are work 0–50, break 50–60, work 60–110, break 110–120 and work 120–150

#### Scenario: Last break dropped
- **WHEN** a 120-minute block has a pattern of 50 minutes of work and 10 minutes of break
- **THEN** it has one break, from 50 to 60, and works from 60 to 120

#### Scenario: No pattern
- **WHEN** a block without a break pattern is running
- **THEN** its phase is `work` until the end

### Requirement: Pause and resume
`POST /api/study/blocks/{block}/timer/pause` SHALL pause the block's running timer, and `POST /api/study/blocks/{block}/timer/resume` SHALL resume its paused timer. A paused timer's time used SHALL NOT grow. Resuming SHALL be allowed only on the block's day. Any other pause or resume SHALL be rejected with a 422 response.

#### Scenario: Pause for a phone call
- **WHEN** a user pauses a running timer and resumes it 8 minutes later
- **THEN** the time left is the same as when it was paused

#### Scenario: Resume on a later day
- **WHEN** a timer paused on Tuesday is resumed on Wednesday
- **THEN** the API responds 422, and the timer stays paused until it is stopped or discarded

#### Scenario: Pause a paused timer
- **WHEN** a user pauses a timer that is already paused
- **THEN** the API responds 422

### Requirement: Block done when its time runs out
When a running timer's time left reaches zero, its run SHALL end at that moment and the block's status SHALL become `done`. This SHALL happen even if no app page is open: before the user's timer, weekly plan or weekly history is returned, and before any timer action, every running timer whose time has run out SHALL be completed as of the moment it ran out.

#### Scenario: Timer runs out
- **WHEN** a 90-minute block's timer has been running for 90 minutes
- **THEN** the block's status is `done` and its recorded time is 90 minutes

#### Scenario: Page closed during the block
- **WHEN** a user starts a 20-minute review block at 09:00, closes the browser, and loads the weekly plan at 11:00
- **THEN** the block is `done` with 20 minutes recorded, and the run ended at 09:20

### Requirement: Stopping early marks the block partial
`POST /api/study/blocks/{block}/timer/stop` SHALL end the block's active run:
- with more than 30 seconds left, the block's status SHALL become `partial`;
- with 30 seconds or less left, the block SHALL be completed as `done` with its full planned time.

The response SHALL include the block with its recorded minutes.

#### Scenario: Stop after 40 minutes
- **WHEN** a user stops a 90-minute block's timer after 40 minutes
- **THEN** the block's status is `partial` and its `actual_minutes` is 40

#### Scenario: Stop at the last second
- **WHEN** a user stops a 90-minute block's timer with 10 seconds left
- **THEN** the block's status is `done` and its `actual_minutes` is 90

### Requirement: Short runs are discarded
Stopping a run that has used less than 60 seconds SHALL discard the run instead. The block's status and recorded time SHALL stay as they were before the run started.

#### Scenario: Started by mistake
- **WHEN** a user starts a planned block and stops it after 20 seconds
- **THEN** the block stays `planned` with no recorded time, and the user has no active timer

### Requirement: Discard a run
`DELETE /api/study/blocks/{block}/timer` SHALL remove the block's active run. The block's status and recorded time SHALL stay as they were before the run started. The route uses the study write rate limiter and responds 403 for demo users.

#### Scenario: Discard a second run
- **WHEN** a `partial` block with 40 recorded minutes is started again and its run is discarded after 15 minutes
- **THEN** the block stays `partial` with 40 recorded minutes

### Requirement: Continue a partial block
A `partial` block dated today SHALL be startable again. Its runs SHALL add up, and the block SHALL become `done` when their total reaches its planned minutes.

#### Scenario: Finish later the same day
- **WHEN** a 90-minute block is stopped as `partial` after 40 minutes, then started again and runs for the remaining 50 minutes
- **THEN** the block is `done` with 90 recorded minutes

### Requirement: Active timer lookup
`GET /api/study/timer` SHALL return:
- `timer`: the user's active timer with its block, or null;
- `recent`: the user's latest run that ended in the last 12 hours, with its block, its end (`finished` or `stopped`) and its minutes, or null;
- `server_time`.

Every timer action SHALL return the same shape. The lookup route uses the study read rate limiter.

#### Scenario: No active timer
- **WHEN** a user without an active timer requests their timer
- **THEN** `timer` is null and `server_time` is returned

#### Scenario: Finished while away
- **WHEN** a user's timer ran out an hour ago while no page was open, and they request their timer
- **THEN** `timer` is null, and `recent` shows that block as `done`, ended `finished`

### Requirement: Timer object
A timer object SHALL include:
- `session_id`;
- `state`: `running` or `paused`;
- `started_at`;
- `planned_seconds`, `used_seconds` and `left_seconds`, as of `server_time`;
- `phase` and `phase_left_seconds`;
- the block's break pattern.

#### Scenario: Running timer object
- **WHEN** a 90-minute block with a 50/10 break pattern has used 52 minutes
- **THEN** its timer shows state `running`, 3120 used seconds, 2280 left seconds, phase `break` and 480 phase seconds left

### Requirement: Timer fields in block responses
Every block in API responses SHALL include:
- `actual_minutes`: the block's recorded time from ended runs, rounded to the nearest minute, or 0;
- `has_recorded_time`: whether the block has any recorded time;
- `timer`: the block's active timer object, or null.

#### Scenario: Weekly plan with a running block
- **WHEN** a user fetches the weekly plan while today's morning block is running
- **THEN** that block has a `timer` with state `running`, and every other block has a null `timer`

### Requirement: Recorded time constrains block edits
When a block has recorded time from ended runs, a block update SHALL respond 422:
- on `status`, for any status other than `done` or `partial`;
- on `block_date`, for a date other than the block's current date;
- on `planned_minutes`, for a changed value that is null or below the recorded time rounded up to whole minutes.

Changing between `done` and `partial`, and every other field, SHALL stay allowed. Blocks without recorded time SHALL keep the ordinary block rules.

#### Scenario: Timer-done block set back to planned
- **WHEN** a user sets a block the timer marked `done` with 90 recorded minutes to `planned`, `missed` or `red`
- **THEN** the API responds 422 on `status` and the block stays `done`

#### Scenario: Judging a recorded block partial
- **WHEN** a user changes that block from `done` to `partial`
- **THEN** the block is saved as `partial` with 90 recorded minutes

#### Scenario: Minutes below the recorded time
- **WHEN** a user sets the planned minutes of a block with 40 recorded minutes to 30
- **THEN** the API responds 422 on `planned_minutes`

### Requirement: Clear recorded time
`DELETE /api/study/blocks/{block}/timer/runs` SHALL delete the block's ended runs and set its status to `planned`, returning the timer lookup payload with the block. It SHALL respond 422 while the block has an active timer, or when the block has no recorded time. The route uses the study write rate limiter and responds 403 for demo users and for another user's block. The Weekly Plan block editor SHALL offer it, after a confirmation, on a block with recorded time.

#### Scenario: Undo a recording
- **WHEN** a user clears the recorded time of a `done` block with 90 recorded minutes
- **THEN** the block is `planned` with no recorded time, and any status can be set on it again

#### Scenario: Clear while running
- **WHEN** a user clears the recorded time of a block whose timer is running
- **THEN** the API responds 422 and the runs stay

### Requirement: Timer access control
Timer routes on another user's block SHALL respond 403. An unknown or malformed block ID SHALL respond 404. Pause, resume, stop or discard on a block without an active timer SHALL respond 422. Run IDs SHALL be exposed as opaque hashed strings.

#### Scenario: Another user's block
- **WHEN** a user stops the timer of a block that belongs to another user
- **THEN** the API responds 403 and that timer keeps running

#### Scenario: Nothing to stop
- **WHEN** a user stops a block that has no active timer
- **THEN** the API responds 422

### Requirement: Timer runs belong to their block
Deleting a block or a weekly plan SHALL delete its timer runs, including an active one. A `weekly_plans` data deletion request SHALL archive, delete and restore runs together with their blocks. A run that is still active when the deletion is carried out SHALL be archived as ended at that moment.

#### Scenario: Data deletion of weekly plans
- **WHEN** a `weekly_plans` request is carried out for a user with 2 plans, 9 blocks and 3 runs
- **THEN** the plans, blocks and runs are deleted, and the archive lists all three runs with their column values

#### Scenario: Restore with runs
- **WHEN** that archive is restored
- **THEN** the blocks get their runs back, and the restored blocks show the same recorded minutes as before

### Requirement: Start from the week grid and the widget
The Weekly Plan page and the This-week widget SHALL show a Start action on each of today's blocks that can start. A running or paused block SHALL show its state and time left instead. While another timer is active, Start SHALL be disabled. Demo users SHALL see Start disabled with a hint.

#### Scenario: Start from the dashboard
- **WHEN** a user selects Start on today's morning block in the This-week widget, and the block has a topic
- **THEN** the timer starts and the widget shows the block as running with its time left

#### Scenario: Tomorrow's block
- **WHEN** a user views the Weekly Plan page on Tuesday
- **THEN** Wednesday's blocks have no Start action

### Requirement: Start dialog
When a non-review block has no topic, or a block has no minutes, Start SHALL open a dialog that asks for them before the timer starts:
- the topic picker lists the user's topics, with topics in the block's lane first;
- the minutes field accepts 5–480.

#### Scenario: Pick a topic first
- **WHEN** a user selects Start on a `major` block without a topic
- **THEN** the dialog asks for a topic, and the timer starts only after one is chosen

### Requirement: Mini timer on every app page
While the user has an active timer, a floating mini timer SHALL stay on top of every `/app` page. It shows the block's name, its time left and its phase. It SHALL persist across navigation and reloads: after a reload it shows at once from a local copy, then corrects itself from the server. While the timer runs, the browser tab title SHALL show the time left.

#### Scenario: Navigate while running
- **WHEN** a user starts a block on the dashboard and opens the Topics page
- **THEN** the mini timer is still shown, counting down

#### Scenario: Reload while running
- **WHEN** a user reloads the page during a running block
- **THEN** the mini timer shows again with the correct time left

### Requirement: Timer panel
Selecting the mini timer SHALL open a panel that shows:
- the block's slot, day, lane and planned task;
- its topic, linked to the topic page;
- time used and time left, with a progress bar;
- the current phase and when the next break starts.

The panel SHALL offer pause or resume, stop and discard. Stop and discard ask for confirmation.

#### Scenario: What am I doing?
- **WHEN** a user selects the running mini timer
- **THEN** the panel shows "Morning deep block", today's date, the planned task "Write Task 2 essay #4", its topic, the time left and the next break

### Requirement: Pop-out timer window
Where the browser supports an always-on-top document window (Document Picture-in-Picture), the panel SHALL offer "Pop out". It opens the timer in a small window that stays above other applications, with pause or resume and stop. Closing the window returns the timer to the page. Where the API is not supported, "Pop out" SHALL be hidden.

#### Scenario: Study in another app
- **WHEN** a user on desktop Chrome selects "Pop out" and switches to a PDF reader
- **THEN** the timer window stays visible above the PDF reader, counting down

#### Scenario: Unsupported browser
- **WHEN** a user opens the panel in a browser without Document Picture-in-Picture
- **THEN** no "Pop out" action is shown

### Requirement: Timer alerts
Every timer alert SHALL appear in the app and play a short sound. It SHALL also show a browser notification when the user has allowed them. The SPA SHALL ask for notification permission when the user first starts a timer. Each alert SHALL fire once, even with the app open in several tabs or after a reload.

#### Scenario: Notifications denied
- **WHEN** a user has denied notifications and a break starts
- **THEN** the alert appears in the app and the sound plays

#### Scenario: Two tabs
- **WHEN** the app is open in two tabs and a block finishes
- **THEN** one notification and one sound are produced

### Requirement: Break alerts
When a break starts, the SPA SHALL alert with the break's length. When a break ends, it SHALL alert the user to return to the block's task. While a break is on, the mini timer SHALL show it as a break.

#### Scenario: Break starts
- **WHEN** a block with a 50/10 pattern reaches 50 minutes
- **THEN** the user is alerted "Break — 10 minutes", and the mini timer shows the break counting down

#### Scenario: Break ends
- **WHEN** that break's 10 minutes are over
- **THEN** the user is alerted to return to the block's planned task

### Requirement: Finish alert opens the wrap-up
When a block's time runs out while the app is open, the SPA SHALL alert that the block is finished. After 5 seconds it SHALL open the block's topic page with the wrap-up, or the Review page for a `review` block. During those 5 seconds the alert SHALL offer "Stay here", which cancels the navigation and keeps a link to the wrap-up. Stopping a block early SHALL offer the wrap-up through a link, without navigating.

#### Scenario: Major block finishes
- **WHEN** a running major block on "Binary trees" reaches its end
- **THEN** the user is alerted, and 5 seconds later the "Binary trees" topic page opens with the wrap-up

#### Scenario: Review block finishes
- **WHEN** a running review block reaches its end
- **THEN** the user is alerted, and 5 seconds later the Review page opens

#### Scenario: Stay on the current page
- **WHEN** a block finishes while the user is typing a practice log, and they select "Stay here"
- **THEN** the page does not change, and the alert keeps a "Log what you learned" link

### Requirement: Wrap-up saves a practice log
The wrap-up SHALL ask "What did you learn?" and for a practice type (default Other). Saving SHALL create a practice log with:
- the block's topic;
- the block's date;
- the block's recorded minutes as the duration;
- the text as details.

The wrap-up can be skipped.

#### Scenario: Log what I learned
- **WHEN** after a finished 90-minute block a user writes "Tree rotations and AVL balance" and saves
- **THEN** a practice log for the topic, dated the block's day with 90 minutes, appears in its practice history

#### Scenario: Skip
- **WHEN** the user skips the wrap-up
- **THEN** no practice log is created, and the block stays `done`

### Requirement: Finished while away
When the app loads and finds that the timer it last showed finished while no app page was open, it SHALL show a "Block finished" notice with a link to the wrap-up. It SHALL NOT navigate on its own.

#### Scenario: Back after the block
- **WHEN** a user's block finished while the browser was closed, and they open the app
- **THEN** the dashboard shows "Block finished" with a "Log what you learned" link, and stays on the dashboard
