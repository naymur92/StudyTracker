# Spec Delta

## Purpose

Decide which review intervals a topic gets. The system provides a library of interval presets and lets each user give a category its own schedule. A short exam track and a career-long track then space reviews differently.

## ADDED Requirements

### Requirement: Built-in preset library
`GET /api/study/schedule-presets` SHALL return the built-in presets. Each preset has:
- `key`
- `name`
- `description`
- `offsets`
- `repeat_every_days`

The library SHALL contain at least these presets:

| Key | Offsets | Repeat |
| --- | --- | --- |
| `standard` | [1, 7, 30, 90] | none |
| `exam_short` | [1, 3, 7, 14] | every 7 days |
| `long_horizon` | [1, 3, 7, 21, 60] | every 90 days |
| `mistakes` | [1, 3, 7] | none |

#### Scenario: Listing presets
- **WHEN** an authenticated user requests the preset library
- **THEN** the response contains the four presets with the offsets and repeat intervals above, in the standard response envelope

### Requirement: Per-category schedule
A user SHALL be able to give a category their own review schedule with `PUT /api/study/categories/{category}/schedule`. The request names either a preset (`preset_key`) or custom `offsets`, plus optional `repeat_every_days` and `repeat_until`.

This works for the user's own categories and for system categories. A schedule on a system category applies only to the user who set it.

#### Scenario: Apply a preset to a category
- **WHEN** a user sets `preset_key` = `exam_short` with `repeat_until` = `2026-12-05` on their "IELTS" category
- **THEN** the category's schedule for that user is offsets [1, 3, 7, 14], every 7 days, until 2026-12-05

#### Scenario: System category stays private to the user
- **WHEN** user A sets a schedule on a system category
- **THEN** user B still gets their own default schedule for topics in that category

### Requirement: Category schedule validation
The system SHALL reject a category schedule with a 422 response if any of these is true:
- `offsets` is empty or has more than 10 values.
- `offsets` is not strictly increasing whole numbers from 1 to 3650.
- `repeat_every_days` is outside 1–365.
- `repeat_until` is in the past.
- `repeat_until` is given without a repeat interval.
- `preset_key` is not a known preset or `custom`.

#### Scenario: Offsets not increasing
- **WHEN** a user submits offsets [1, 7, 7, 30]
- **THEN** the API responds 422 with a validation error on `offsets`

#### Scenario: Until date without a repeat interval
- **WHEN** a user submits `repeat_until` without `repeat_every_days` on a custom schedule
- **THEN** the API responds 422 with a validation error on `repeat_until`

### Requirement: Read and remove a category schedule
`GET /api/study/categories/{category}/schedule` SHALL return the user's schedule for that category, or null with the fallback that will apply: `user_default` or `system_default`. `DELETE` on the same path SHALL remove the category schedule.

#### Scenario: Remove a category schedule
- **WHEN** a user deletes the schedule of a category
- **THEN** a later GET returns no category schedule and names the fallback that new topics in that category will use

### Requirement: Category resource shows the schedule
Category listings SHALL return `review_schedule` for each category. This is the requesting user's schedule (`preset_key`, `offsets`, `repeat_every_days`, `repeat_until`), or null when the category uses the default.

#### Scenario: Category list with a schedule
- **WHEN** a user with a `long_horizon` schedule on "Maths" lists categories
- **THEN** the "Maths" entry returns `review_schedule.preset_key` = `long_horizon` and the other categories return null

### Requirement: Schedule resolution order
When a topic is created, its schedule SHALL be the first one found in this order:
1. The user's schedule for the topic's category.
2. The user's active default revision templates.
3. The system default templates.
4. The built-in offsets [1, 7, 30, 90].

Mistake entries SHALL always use the `mistakes` preset.

#### Scenario: Category schedule wins
- **WHEN** a user with custom default templates [2, 10] creates a topic in a category with a `long_horizon` schedule
- **THEN** the topic's schedule is offsets [1, 3, 7, 21, 60] repeating every 90 days

#### Scenario: Fallback to user templates
- **WHEN** the same user creates a topic in a category without a schedule
- **THEN** the topic's schedule is offsets [2, 10] with no repeat

### Requirement: Schedule snapshot on topic creation
A topic SHALL keep the schedule it was created with. Later changes to category schedules or default templates SHALL NOT change an existing topic's schedule or task dates. Changing a topic's category SHALL NOT change its schedule either.

#### Scenario: Template change after creation
- **WHEN** a user changes their default templates after creating a topic
- **THEN** the topic's stored schedule and pending revision dates stay the same

### Requirement: Apply a category schedule to existing topics
A category schedule update with `apply_to_existing_topics` = true SHALL replace the stored schedule of the user's existing topics in that category that are not archived. Pending task dates SHALL NOT move; the new schedule takes effect at each topic's next scheduling event.

#### Scenario: Exam date added later
- **WHEN** a user adds `repeat_until` = `2026-12-05` to the "IELTS" schedule with `apply_to_existing_topics` = true
- **THEN** existing active IELTS topics store the new repeat-until date and keep their pending revision dates until their next graded review

### Requirement: Initial revisions follow the snapshot
When a topic is created, its pending revisions SHALL be scheduled at the first study date plus each offset. If the schedule repeats, the system SHALL add one `repeat` review at the last offset plus the repeat interval, unless that date is after the repeat-until date.

#### Scenario: Topic created under the exam preset
- **WHEN** a topic with first study date 2026-10-11 is created in a category using `exam_short` until 2026-12-05
- **THEN** pending revisions are created for 2026-10-12, 2026-10-14, 2026-10-18 and 2026-10-25, plus a repeat review on 2026-11-01

#### Scenario: Default schedule keeps today's behavior
- **WHEN** a user without category schedules or custom templates creates a topic
- **THEN** revisions are created at +1, +7, +30 and +90 days with the same titles as before this change

### Requirement: Category schedule access control
Category schedule endpoints SHALL respond:
- 404 for a category that does not exist or that the user cannot see;
- 403 for a category owned by another user.

The PUT and DELETE routes SHALL reject demo users with 403. All routes use the study read/write rate limiters.

#### Scenario: Another user's category
- **WHEN** a user requests the schedule of a category owned by another user
- **THEN** the API responds 403

#### Scenario: Demo user edits a schedule
- **WHEN** the demo user submits a category schedule
- **THEN** the API responds 403 with the demo-restriction message

### Requirement: Schedule management in the UI
The Categories page SHALL show each category's review schedule, or "Default". Users SHALL be able to choose a preset or enter custom offsets, an optional repeat interval and a repeat-until date. The topic create page SHALL show which schedule the chosen category will apply.

#### Scenario: Choosing a category on the create page
- **WHEN** a user selects a category with the `exam_short` schedule while creating a topic
- **THEN** the form shows that reviews will happen at +1, +3, +7 and +14 days, then weekly until the configured date
