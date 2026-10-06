# Spec Delta

## Purpose

Plan each week as a gear (Green, Yellow or Red) and a set of pre-decided study blocks, then score the week as blocks done ÷ blocks planned against the user's success line. A week that goes badly becomes information to plan with, not a verdict.

## ADDED Requirements

### Requirement: Weekly plan lookup
`GET /api/study/weekly-plan` SHALL accept an optional `date` (default: today in the application timezone). It SHALL return:
- `week_start` and `week_end` of the week containing the date, based on the user's `week_starts_on`;
- the user's plan for that week, or null when none exists;
- the plan's blocks, ordered by date and then slot;
- `score`, `stats` and `success_threshold_percent`.

The route uses the study read rate limiter.

#### Scenario: Week without a plan
- **WHEN** a user whose week starts on Sunday requests the plan for Wednesday 2026-10-14 without having planned that week
- **THEN** the response has `week_start` 2026-10-11, `week_end` 2026-10-17 and a null plan

#### Scenario: Existing plan
- **WHEN** the user has a plan for that week
- **THEN** the response includes its gear, focus fields, reflection fields, blocks and score

### Requirement: Create a weekly plan
`POST /api/study/weekly-plan` SHALL create a plan from:
- `week_start`: required; its weekday must equal the user's `week_starts_on`;
- `gear`: required; `green`, `yellow` or `red`;
- optional `major_focus` and `minor_focus`, each ≤200 characters;
- `generate_blocks`: optional, default true.

Past and future weeks are allowed. A plan that would overlap an existing plan of the user SHALL be rejected with a 422 response. The route rejects demo users with 403.

#### Scenario: Plan next week in advance
- **WHEN** a user creates a plan for the week of 2026-10-18 with gear `green`
- **THEN** the API responds 201 with the plan and its generated blocks

#### Scenario: Wrong start weekday
- **WHEN** a user whose week starts on Sunday submits `week_start` 2026-10-14 (a Wednesday)
- **THEN** the API responds 422 with a validation error on `week_start`

#### Scenario: Duplicate week
- **WHEN** a plan already exists for the week of 2026-10-11 and the user creates another one for it
- **THEN** the API responds 422 and no plan is created

### Requirement: Generated blocks follow the user's off days
When blocks are generated, the system SHALL treat each day of the week as an off day if its weekday is in the user's `off_days`, and as an office day otherwise. It SHALL then create the blocks of the chosen gear's template for that kind of day.

#### Scenario: Custom off days
- **WHEN** a user with `off_days` [0, 6] (Sunday and Saturday) generates a green week
- **THEN** Sunday and Saturday get off-day blocks, and Monday to Friday get office-day blocks

### Requirement: Green gear template
The Green template SHALL create these blocks:

| Day | Blocks |
| --- | --- |
| Office day | morning (major, 90 min), review (review, 20 min), minor (minor, 20 min) |
| Off day | block A (major, 150 min), block B (minor, 75 min), review (review, 15 min) |

#### Scenario: Green week with Friday and Saturday off
- **WHEN** a user with `off_days` [5, 6] creates a green plan for the week starting Sunday 2026-10-11
- **THEN** 21 blocks are generated: 3 on each day from Sunday to Thursday, and block A, block B and review on Friday and on Saturday

### Requirement: Yellow gear template
The Yellow template SHALL create these blocks:

| Day | Blocks |
| --- | --- |
| Office day | morning (major, 90 min), review (review, 15 min) |
| Off day | review (review, 15 min) |
| First off day of the week | also block A (major, 150 min) |

#### Scenario: Yellow week
- **WHEN** a user with `off_days` [5, 6] creates a yellow plan for the week starting Sunday 2026-10-11
- **THEN** 13 blocks are generated, and Friday 2026-10-16 is the only off day with a block A

### Requirement: Red gear template
The Red template SHALL create one review block (review, 20 minutes) on every day of the week.

#### Scenario: Red week
- **WHEN** a user creates a red plan for the week starting Sunday 2026-10-11
- **THEN** 7 review blocks of 20 minutes are generated, one per day

### Requirement: Update a weekly plan
`PATCH /api/study/weekly-plan/{week}` SHALL update any of these, without changing the blocks:
- `gear`
- `major_focus` and `minor_focus`
- `reflection`, ≤2000 characters
- `if_then_plan`, ≤500 characters
- `output_note`, ≤300 characters

`DELETE` on the same path SHALL delete the plan and its blocks. Both routes respond 403 for another user's plan and for demo users.

#### Scenario: Record the weekly review
- **WHEN** a user saves a reflection, an if-then plan ("If the office runs late, then do 10 minutes of due reviews only") and an output note
- **THEN** the plan returns the saved text, and its blocks stay the same

### Requirement: Regenerate remaining blocks for a new gear
`POST /api/study/weekly-plan/{week}/regenerate` with a `gear` SHALL:
- set the plan's gear;
- replace the blocks dated today or later that still have status `planned` with that gear's template blocks for the same dates.

Past blocks, and blocks with any other status, SHALL NOT change.

#### Scenario: Downshift mid-week
- **WHEN** on Tuesday a user regenerates a green plan as `yellow`
- **THEN** the planned blocks from Tuesday to Saturday are replaced with yellow-template blocks, while Sunday's and Monday's blocks and any block already marked done stay the same

### Requirement: Manage blocks
The system SHALL let a user add, update and delete blocks:
- `POST /api/study/weekly-plan/{week}/blocks`
- `PATCH /api/study/blocks/{block}`
- `DELETE /api/study/blocks/{block}`

The routes use the study write rate limiter and respond 403 for demo users.

#### Scenario: Add a block
- **WHEN** a user adds an `other` block to Thursday of a planned week
- **THEN** the API responds 201, and the block appears in the week's blocks

#### Scenario: Delete a block
- **WHEN** a user deletes a block
- **THEN** it no longer appears in the week, and the score is recalculated without it

### Requirement: Block placement fields
A block SHALL have these placement fields, and the system SHALL reject invalid values with a 422 response:

| Field | Rules |
| --- | --- |
| `block_date` | within the plan's week |
| `slot` | `morning`, `block_a`, `block_b`, `review`, `minor` or `other` |
| `lane` | `major`, `minor`, `review` or `work` |
| `category_id`, `topic_id` | optional; must be ones the user can use |

#### Scenario: Block outside the week
- **WHEN** a user adds a block dated outside the plan's week
- **THEN** the API responds 422 with a validation error on `block_date`

#### Scenario: Unknown lane
- **WHEN** a user sets a block's `lane` to `deep`
- **THEN** the API responds 422 with a validation error on `lane`

### Requirement: Block content and status fields
A block SHALL have these content fields, and the system SHALL reject invalid values with a 422 response:

| Field | Rules |
| --- | --- |
| `planned_task` | ≤300 characters |
| `planned_minutes` | 5–480 |
| `status` | `planned`, `done`, `partial`, `missed` or `red`; defaults to `planned` |
| `note` | ≤500 characters |

#### Scenario: Pre-decide tomorrow's first task
- **WHEN** a user sets `planned_task` "Write Task 2 essay #4" on tomorrow's morning block
- **THEN** the block returns that planned task

#### Scenario: Invalid status
- **WHEN** a user sets a block's `status` to `skipped`
- **THEN** the API responds 422 with a validation error on `status`

#### Scenario: Mark a red day
- **WHEN** a user marks all blocks of an Eid day as `red`
- **THEN** those blocks are excluded from the week's score

### Requirement: Weekly score
The plan `score` SHALL count only blocks that are not `red` and that are either dated before today or have status `done`, `partial` or `missed`. A `planned` block dated before today counts as missed. The score SHALL return:
- `percent`: round(100 × (done + 0.5 × partial) ÷ counted), or null when nothing is counted;
- `on_track`: `percent` ≥ the user's success threshold;
- the total number of planned blocks for the week, excluding red blocks.

#### Scenario: Above the success line
- **WHEN** 10 blocks have been counted so far (7 done, 2 partial, 1 missed) and 1 more block is `red`
- **THEN** `percent` is 80 and `on_track` is true with an 80% threshold

#### Scenario: Unmarked past block
- **WHEN** yesterday's morning block still has status `planned`
- **THEN** it counts as missed in the score

#### Scenario: Today's unmarked blocks
- **WHEN** today's blocks still have status `planned`
- **THEN** they are not yet counted

### Requirement: Weekly stats
The weekly plan response SHALL include `stats` with:
- the current number of topics with overdue reviews;
- the number of revisions completed during the week and their total recorded review minutes;
- the regular topics created during the week, with the weekly new-topic cap.

#### Scenario: Stats for the current week
- **WHEN** a user completed 18 revisions this week with 52 recorded minutes, created 4 topics and has 3 topics overdue
- **THEN** `stats` shows those values with the cap from the user's preferences

### Requirement: Weekly history
`GET /api/study/weekly-plan/history` SHALL return the user's most recent plans, newest first. It accepts `weeks` from 1 to 26 (default 8). Each entry SHALL have `week_start`, `gear`, `percent`, done and counted totals.

#### Scenario: Eight-week history
- **WHEN** a user with 10 past plans requests history without parameters
- **THEN** the 8 most recent plans are returned with their scores

### Requirement: Weekly plan page
The SPA SHALL provide a Weekly Plan page with:
- week navigation;
- a gear selector with the approximate weekly hours of each gear;
- major and minor focus fields;
- a score bar with a marker at the success line;
- the overdue count.

A week without a plan SHALL offer to create one from a chosen gear.

#### Scenario: Plan this week from the page
- **WHEN** a user opens an unplanned week, chooses Green and confirms
- **THEN** the page shows the generated blocks and the score bar appears with the 80% marker

### Requirement: Block grid and weekly review on the page
The Weekly Plan page SHALL show a seven-day grid of blocks. Each block's status can be set to done, partial, missed or red, and blocks can be added, edited and removed. The page SHALL include the weekly reflection fields and a "Plan next week" action.

#### Scenario: Marking a block done
- **WHEN** a user marks today's morning block as done
- **THEN** the block shows as done and the score updates

#### Scenario: Plan next week from the review
- **WHEN** a user finishes the reflection and chooses "Plan next week" with gear Yellow
- **THEN** next week's plan is created with yellow blocks, and the page navigates to it

### Requirement: This-week dashboard widget
The Dashboard SHALL show a "This week" widget with:
- the current gear;
- the score so far against the success line;
- today's blocks with their planned tasks and quick done, partial and missed actions;
- "Next up": the first unmarked block of today that has a planned task, or a link to the oldest due review when there is none.

With no plan for the current week, the widget SHALL link to planning the week.

#### Scenario: Nothing pre-decided
- **WHEN** today's blocks have no planned task and reviews are overdue
- **THEN** "Next up" links to the review page, starting with the oldest due review

#### Scenario: No plan yet
- **WHEN** the current week has no plan
- **THEN** the widget shows a "Plan this week" link to the Weekly Plan page

### Requirement: Weekly plan access control
Plans and blocks SHALL be visible only to their owner, and their IDs SHALL be exposed as opaque hashed strings. A request for another user's plan or block SHALL receive 403. An unknown or malformed ID SHALL receive 404.

#### Scenario: Another user's block
- **WHEN** a user updates a block that belongs to another user
- **THEN** the API responds 403 and the block stays unchanged
