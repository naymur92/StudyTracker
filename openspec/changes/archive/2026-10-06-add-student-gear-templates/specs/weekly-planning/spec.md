# Spec Delta

## ADDED Requirements

### Requirement: Templates follow the study profile
When blocks are generated, the system SHALL use the gear templates of the user's current `study_profile`: the job-holder templates or the student templates.

#### Scenario: Same gear, different profile
- **WHEN** a job holder and a student, both with `off_days` [5, 6], each create a green plan for the week starting Sunday 2026-10-11
- **THEN** the job holder gets 21 blocks and the student gets 26 blocks

### Requirement: Profile change keeps existing blocks
Changing `study_profile` SHALL NOT change blocks that already exist. Only blocks generated afterwards use the new profile's templates: blocks of a new plan, and blocks replaced by a regeneration.

#### Scenario: Switching profile mid-week
- **WHEN** a job holder with a green plan switches to `student` and then regenerates the plan as `yellow` on Tuesday
- **THEN** the blocks before Tuesday and the blocks already marked keep their job-holder slots, and the regenerated blocks from Tuesday on come from the student Yellow template

### Requirement: Student green gear template
The student Green template SHALL create these blocks:

| Day | Blocks |
| --- | --- |
| Class day | class recap (major, 30 min), deep block (major, 90 min), review (review, 25 min), minor (minor, 30 min) |
| Free day | block A (major, 150 min), block B (minor, 120 min), review (review, 25 min) |

#### Scenario: Student green week
- **WHEN** a student with `off_days` [5, 6] creates a green plan for the week starting Sunday 2026-10-11
- **THEN** 26 blocks totalling 1465 planned minutes are generated: 4 on each class day from Sunday to Thursday, and block A, block B and review on Friday and on Saturday

### Requirement: Student yellow gear template
The student Yellow template, for assignment or deadline weeks, SHALL create these blocks:

| Day | Blocks |
| --- | --- |
| Class day | class recap (major, 20 min), deep block (major, 60 min), review (review, 20 min) |
| Free day | block A (major, 120 min), review (review, 20 min) |

#### Scenario: Student yellow week
- **WHEN** a student with `off_days` [5, 6] creates a yellow plan for the week starting Sunday 2026-10-11
- **THEN** 19 blocks totalling 780 planned minutes are generated, with a block A on both Friday and Saturday

### Requirement: Student red gear template
The student Red template SHALL create one review block (review, 20 minutes) on every day of the week.

#### Scenario: Student red week
- **WHEN** a student creates a red plan for the week starting Sunday 2026-10-11
- **THEN** 7 review blocks of 20 minutes are generated, one per day

### Requirement: Gear options
The weekly plan response SHALL include `gear_options`. It lists, for each gear (`green`, `yellow`, `red`):
- a label;
- a description in the user's profile terms;
- `minutes`: the total planned minutes the template would produce for a full week, given the user's profile and `off_days`;
- `hours`: those minutes divided by 60, rounded to the nearest hour.

#### Scenario: Student options
- **WHEN** a student with `off_days` [5, 6] requests the weekly plan
- **THEN** `gear_options` shows green 1465 minutes (24 h), yellow 780 minutes (13 h) and red 140 minutes (2 h)

#### Scenario: Job-holder options
- **WHEN** a job holder with `off_days` [5, 6] requests the weekly plan
- **THEN** `gear_options` shows green 1130 minutes (19 h), yellow 705 minutes (12 h) and red 140 minutes (2 h)

### Requirement: Profile-aware weekly wording
The Weekly Plan page and the This-week widget SHALL show each gear's hours and description from `gear_options`. They SHALL describe days as "class days / free days" for a student and as "office days / off days" for a job holder.

#### Scenario: Student sees class days
- **WHEN** a student opens a week without a plan
- **THEN** the page explains that blocks are generated from class days and free days, and each gear shows the student hours (Green ≈ 24 h)

## MODIFIED Requirements

### Requirement: Weekly plan lookup
`GET /api/study/weekly-plan` SHALL accept an optional `date` (default: today in the application timezone). It SHALL return:
- `week_start` and `week_end` of the week containing the date, based on the user's `week_starts_on`;
- the user's plan for that week, or null when none exists;
- the plan's blocks, ordered by date and then slot;
- `score`, `stats` and `success_threshold_percent`;
- `study_profile` and `gear_options`.

The route uses the study read rate limiter.

#### Scenario: Week without a plan
- **WHEN** a user whose week starts on Sunday requests the plan for Wednesday 2026-10-14 without having planned that week
- **THEN** the response has `week_start` 2026-10-11, `week_end` 2026-10-17, a null plan, and `gear_options` for the user's profile

#### Scenario: Existing plan
- **WHEN** the user has a plan for that week
- **THEN** the response includes its gear, focus fields, reflection fields, blocks and score

### Requirement: Generated blocks follow the user's off days
When blocks are generated, the system SHALL treat each day of the week as an off day if its weekday is in the user's `off_days`, and as a working day otherwise. Working days are office days for a job holder and class days for a student; off days are free days for a student. The system SHALL then create the blocks of the chosen gear's template, for the user's profile, for that kind of day.

#### Scenario: Custom off days
- **WHEN** a job holder with `off_days` [0, 6] (Sunday and Saturday) generates a green week
- **THEN** Sunday and Saturday get off-day blocks, and Monday to Friday get office-day blocks

#### Scenario: Student free days
- **WHEN** a student with `off_days` [5, 6] generates a green week
- **THEN** Friday and Saturday get free-day blocks, and Sunday to Thursday get class-day blocks

### Requirement: Green gear template
The job-holder Green template SHALL create these blocks:

| Day | Blocks |
| --- | --- |
| Office day | morning (major, 90 min), review (review, 20 min), minor (minor, 20 min) |
| Off day | block A (major, 150 min), block B (minor, 75 min), review (review, 15 min) |

#### Scenario: Green week with Friday and Saturday off
- **WHEN** a job holder with `off_days` [5, 6] creates a green plan for the week starting Sunday 2026-10-11
- **THEN** 21 blocks are generated: 3 on each day from Sunday to Thursday, and block A, block B and review on Friday and on Saturday

### Requirement: Yellow gear template
The job-holder Yellow template SHALL create these blocks:

| Day | Blocks |
| --- | --- |
| Office day | morning (major, 90 min), review (review, 15 min) |
| Off day | review (review, 15 min) |
| First off day of the week | also block A (major, 150 min) |

#### Scenario: Yellow week
- **WHEN** a job holder with `off_days` [5, 6] creates a yellow plan for the week starting Sunday 2026-10-11
- **THEN** 13 blocks are generated, and Friday 2026-10-16 is the only off day with a block A

### Requirement: Red gear template
The job-holder Red template SHALL create one review block (review, 20 minutes) on every day of the week.

#### Scenario: Red week
- **WHEN** a job holder creates a red plan for the week starting Sunday 2026-10-11
- **THEN** 7 review blocks of 20 minutes are generated, one per day

### Requirement: Block placement fields
A block SHALL have these placement fields, and the system SHALL reject invalid values with a 422 response:

| Field | Rules |
| --- | --- |
| `block_date` | within the plan's week |
| `slot` | `morning`, `class_recap`, `deep`, `block_a`, `block_b`, `review`, `minor` or `other` |
| `lane` | `major`, `minor`, `review` or `work` |
| `category_id`, `topic_id` | optional; must be ones the user can use |

#### Scenario: Block outside the week
- **WHEN** a user adds a block dated outside the plan's week
- **THEN** the API responds 422 with a validation error on `block_date`

#### Scenario: Unknown lane
- **WHEN** a user sets a block's `lane` to `deep`
- **THEN** the API responds 422 with a validation error on `lane`

#### Scenario: New slots accepted
- **WHEN** a user adds a block with `slot` = `class_recap`
- **THEN** the API responds 201, and the block appears before the deep block of that day
