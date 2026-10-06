# Spec Delta

## Purpose

Decide when each topic is next reviewed. A topic moves through an ordered list of review offsets (its schedule), and each review's recall grade decides how far it moves. Remembered material is spaced further apart; forgotten material comes back the next day.

## ADDED Requirements

### Requirement: Topic schedule state
The system SHALL keep scheduling state for every topic:
- `srs_step`: the number of schedule steps passed.
- `srs_lapses`: the number of failed recalls.
- `last_reviewed_on`: the date of the last review.

The topic resource SHALL return these together with:
- `srs_steps_total`: the number of offsets in the topic's schedule.
- `next_review_date`: the earliest pending or missed revision date, or null.

#### Scenario: New topic starts at step zero
- **WHEN** a user creates a topic with a 4-offset schedule
- **THEN** the topic resource returns `srs_step` 0, `srs_lapses` 0, `srs_steps_total` 4 and `last_reviewed_on` null
- **AND** `next_review_date` is the date of its first pending revision

### Requirement: Recall grade on revision completion
`POST /api/study/tasks/{task}/complete` SHALL accept an optional `recall_grade` of `again`, `hard`, `good` or `easy`. The grade is valid only for tasks of type `revision`.

The system SHALL store the grade on the task, and the task resource SHALL return it as `recall_grade`.

#### Scenario: Grade stored on completion
- **WHEN** a user completes a pending revision task with `recall_grade` = `good`
- **THEN** the task becomes `completed` and its resource returns `recall_grade` = `good`

#### Scenario: Grade rejected for a learn task
- **WHEN** a user completes a `learn` task with `recall_grade` = `easy`
- **THEN** the API responds 422 with a validation error on `recall_grade` and the task stays unchanged

#### Scenario: Unknown grade value
- **WHEN** a user sends `recall_grade` = `medium`
- **THEN** the API responds 422 and the task stays unchanged

### Requirement: Review date is the completion date
The review date for scheduling SHALL be the current date in the application timezone when the completion is processed. The task's scheduled date is not used.

#### Scenario: Late review anchors on the actual day
- **WHEN** a revision scheduled for 2026-10-10 is completed with a grade on 2026-10-13
- **THEN** the next review dates are calculated from 2026-10-13

### Requirement: Good advances one step
A `good` grade SHALL increase `srs_step` by one. When steps remain, the next review is on the review date plus the gap between the offset just tested and the next offset. The offset just tested is offset number `srs_step + 1`, taken before the increase.

#### Scenario: Good on the second step
- **WHEN** a topic with offsets [1, 7, 30, 90] and `srs_step` 1 has its revision graded `good` on 2026-10-14
- **THEN** `srs_step` becomes 2 and the next review is scheduled for 2026-11-06 (23 days later)

#### Scenario: On-time Good reviews match the fixed schedule
- **WHEN** a topic first studied on 2026-10-07 with offsets [1, 7, 30, 90] is reviewed on each scheduled date and every review is graded `good`
- **THEN** the reviews fall on 2026-10-08, 2026-10-14, 2026-11-06 and 2027-01-05

### Requirement: Easy skips one step
An `easy` grade SHALL increase `srs_step` by two, capped at the number of offsets. When steps remain, the next review is on the review date plus the gap between the offset just tested and the offset after the skipped one.

#### Scenario: Easy on the first step
- **WHEN** a topic with offsets [1, 7, 30, 90] and `srs_step` 0 has its revision graded `easy` on 2026-10-08
- **THEN** `srs_step` becomes 2 and the next review is scheduled for 2026-11-06 (29 days later)

#### Scenario: Easy near the end of the schedule
- **WHEN** a topic with 4 offsets and `srs_step` 3 is graded `easy`
- **THEN** `srs_step` becomes 4 and the topic follows the end-of-schedule rules

### Requirement: Hard schedules a relearn check tomorrow
A `hard` grade SHALL leave `srs_step` unchanged. The next review, a relearn check of the same step, is scheduled for the day after the review date.

#### Scenario: Hard then Good resumes normal spacing
- **WHEN** a topic with offsets [1, 7, 30, 90] and `srs_step` 2 is graded `hard` on 2026-11-06
- **THEN** `srs_step` stays 2 and a relearn check is scheduled for 2026-11-07
- **AND WHEN** that relearn check is graded `good` on 2026-11-07
- **THEN** `srs_step` becomes 3 and the next review is scheduled for 2027-01-06 (60 days later)

### Requirement: Again restarts the schedule
An `again` grade SHALL:
- set `srs_step` to 0;
- increase `srs_lapses` by one;
- schedule a relearn check of the first step for the day after the review date.

Later steps are spaced again from the start of the schedule.

#### Scenario: Again on the third step
- **WHEN** a topic with offsets [1, 7, 30, 90] and `srs_step` 2 is graded `again` on 2026-11-06
- **THEN** `srs_step` becomes 0, `srs_lapses` increases by one, and a relearn check is scheduled for 2026-11-07
- **AND WHEN** that relearn check is graded `good` on 2026-11-07
- **THEN** `srs_step` becomes 1 and the next review is scheduled for 2026-11-13

### Requirement: Pending revisions are re-planned after a graded review
After a graded review, the system SHALL re-plan the topic's other revision tasks that are `pending` or `missed`. They are moved onto a projected sequence that assumes every later review is graded `good`.

The system SHALL:
- create tasks when the sequence needs more;
- delete pending tasks the sequence does not need;
- reset re-dated `missed` tasks to `pending`.

Completed and skipped tasks SHALL NOT change.

#### Scenario: Again expands the remaining plan
- **WHEN** a topic with offsets [1, 7, 30, 90], `srs_step` 2 and one pending revision is graded `again` on 2026-11-06
- **THEN** the topic has pending revisions on 2026-11-07, 2026-11-13, 2026-12-06 and 2027-02-04, and no others

#### Scenario: Backlog collapses after a review
- **WHEN** a topic has Revision 2 and Revision 3 both `missed`, and the user grades Revision 2 `good` today
- **THEN** Revision 3 moves to a future date with status `pending`, and no revision of that topic stays overdue

#### Scenario: Completed history is preserved
- **WHEN** a topic with two completed revisions is graded `again`
- **THEN** the two completed revisions keep their dates, statuses and grades

### Requirement: Projected reviews carry a kind and sequential numbers
Revision tasks created or moved by scheduling SHALL have a `review_kind`:
- `step`: a normal schedule step;
- `relearn`: a next-day check after `hard` or `again`;
- `repeat`: a maintenance review after the final step.

Their `revision_no` values SHALL continue in date order after the highest `revision_no` among the topic's completed and skipped revisions.

#### Scenario: Relearn check is labelled
- **WHEN** a revision is graded `hard`
- **THEN** the next-day task has `review_kind` = `relearn` and its `task_type_label` identifies it as a relearn check

#### Scenario: Numbering continues after history
- **WHEN** a topic whose highest completed revision is number 3 is re-planned into three pending reviews
- **THEN** those reviews are numbered 4, 5 and 6 in date order

### Requirement: Repeat tail after the final step
When a topic's schedule has a repeat interval and the final step has been passed, every review graded `good` or `easy` SHALL schedule the next review for the review date plus the repeat interval. If the schedule has a repeat-until date, no review is scheduled after that date. Projections include at most one repeat review.

#### Scenario: Weekly reviews until an exam date
- **WHEN** a topic with offsets [1, 3, 7, 14], a 7-day repeat until 2026-12-05 and `srs_step` 3 is graded `good` on 2026-11-20
- **THEN** `srs_step` becomes 4 and a `repeat` review is scheduled for 2026-11-27
- **AND WHEN** reviews are graded `good` on 2026-11-27 and 2026-12-04
- **THEN** a review is scheduled for 2026-12-04, and none after it, because 2026-12-11 is past the repeat-until date

### Requirement: Graduation without a repeat rule
When a topic without a repeat interval passes its final step, the system SHALL leave it with no pending revisions. The topic resource SHALL return `next_review_date` = null.

#### Scenario: Final step passed
- **WHEN** a topic with offsets [1, 7, 30, 90] and `srs_step` 3 is graded `good`
- **THEN** `srs_step` becomes 4, the topic has no pending revisions, and `next_review_date` is null

### Requirement: Ungraded completion keeps fixed dates
Completing a revision without `recall_grade` SHALL:
- mark it completed;
- increase `srs_step` by one, capped at the number of offsets;
- set `last_reviewed_on`;
- leave every other task's date and status unchanged.

#### Scenario: Legacy client completes a revision
- **WHEN** a client completes a revision with only `difficulty_feedback` = `medium`
- **THEN** the task is completed with that `difficulty_feedback`, `srs_step` increases by one, and the topic's other pending revision dates stay the same

### Requirement: Learn completion re-anchors the plan
Completing a topic's `learn` task SHALL re-plan its pending revisions from the completion date. The first review falls on the completion date plus the first offset, and the rest follow the schedule gaps. This applies only while the topic has no completed revision.

#### Scenario: Learned three days late
- **WHEN** a topic with offsets [1, 7, 30, 90] and first study date 2026-10-07 has its learn task completed on 2026-10-10
- **THEN** its pending revisions are scheduled for 2026-10-11, 2026-10-17, 2026-11-09 and 2027-01-08

#### Scenario: Learned on the planned day
- **WHEN** the learn task is completed on the topic's first study date
- **THEN** the pending revision dates stay the same

#### Scenario: Revisions already completed
- **WHEN** the learn task is completed after one of the topic's revisions was already completed
- **THEN** no revision dates change

### Requirement: Review duration is recorded
`POST /api/study/tasks/{task}/complete` SHALL accept an optional `review_seconds` for revision tasks: an integer from 1 to 3600. The system SHALL store it and return it in the task resource.

#### Scenario: Duration stored
- **WHEN** a user grades a revision with `review_seconds` = 140
- **THEN** the task resource returns `review_seconds` = 140

#### Scenario: Duration out of range
- **WHEN** a client sends `review_seconds` = 0 or 7200
- **THEN** the API responds 422 and the task stays unchanged

### Requirement: Completion response reports the scheduling outcome
The completion response SHALL include `schedule_outcome` with:
- `srs_step`
- `next_review_date`: a date or null
- `next_review_kind`
- `graduated`: true when no review is scheduled

`schedule_outcome` SHALL be null when the completion did not change scheduling.

#### Scenario: Outcome after a graded review
- **WHEN** a revision is graded `good` and the next review falls on 2026-11-06
- **THEN** the response includes `schedule_outcome.next_review_date` = `2026-11-06` and `graduated` = false

### Requirement: Existing topics adopt adaptive scheduling
For a topic created before this capability existed, the first scheduling action SHALL do two things:
- resolve and store the topic's schedule from the user's current schedule settings;
- set `srs_step` to the number of completed revisions, capped at the number of offsets.

No migration step SHALL change existing task dates.

#### Scenario: Legacy topic graded for the first time
- **WHEN** a topic created before this change, with two completed revisions under the default [1, 7, 30, 90] schedule, has its third revision graded `good`
- **THEN** the topic stores offsets [1, 7, 30, 90] and `srs_step` becomes 3

### Requirement: Grading is safe against duplicate submissions
Grading and re-planning for one topic SHALL be atomic. Completing an already completed task SHALL be rejected without changing scheduling state.

#### Scenario: Double submit
- **WHEN** the same revision completion is submitted twice in quick succession
- **THEN** exactly one completion takes effect, the second request receives a 422 "already completed" error, and the topic's pending revisions reflect a single re-plan
