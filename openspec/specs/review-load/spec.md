# review-load Specification

## Purpose

Keep the daily review load visible and bounded. The system estimates today's review time, warns when it passes the user's budget, and raises soft warnings for review debt, too many new topics in a week, and back-to-back missed days.

## Requirements

### Requirement: Review load endpoint
`GET /api/study/review-load` SHALL accept an optional `date` (default: today in the application timezone). It SHALL return:
- `due_topics`: topics with a due review on or before the date;
- `overdue_topics`;
- `per_review_minutes`;
- `estimated_minutes`: `due_topics` × `per_review_minutes`, rounded up;
- `budget_minutes`;
- `over_budget`: true when `estimated_minutes` > `budget_minutes`.

The route uses the study read rate limiter.

#### Scenario: Load under budget
- **WHEN** 6 topics are due, the per-review estimate is 3 minutes and the budget is 25
- **THEN** the response shows `due_topics` 6, `estimated_minutes` 18 and `over_budget` false

#### Scenario: Load over budget
- **WHEN** 10 topics are due with the same settings
- **THEN** the response shows `estimated_minutes` 30 and `over_budget` true

### Requirement: Per-review time estimate
`per_review_minutes` SHALL come from the user's 30 most recent graded reviews that recorded a duration:
- with at least 5 such reviews, it is their mean duration in minutes, clamped to 1–15;
- otherwise it is the user's `minutes_per_review` preference.

#### Scenario: Not enough timing history
- **WHEN** a user has 3 timed reviews and `minutes_per_review` = 3
- **THEN** `per_review_minutes` is 3

#### Scenario: Estimate from history
- **WHEN** a user's 10 most recent timed reviews average 150 seconds
- **THEN** `per_review_minutes` is 2.5

### Requirement: Dashboard includes the review load
`GET /api/study/dashboard` SHALL include the review-load object for the requested date as `stats.review_load`.

#### Scenario: Dashboard payload
- **WHEN** a user loads the dashboard
- **THEN** the response includes `stats.review_load` with the same fields as the review-load endpoint

### Requirement: Due-today banner
The Dashboard and Daily Tasks pages SHALL show a banner such as "Due today: N topics ≈ M min" with a link to start a review.

When the load is over budget, the banner SHALL be shown as a warning advising to do the oldest reviews first and let the rest carry over. When nothing is due, the banner SHALL say so.

#### Scenario: Over-budget banner
- **WHEN** 10 topics are due and the estimate is 30 minutes against a 25-minute budget
- **THEN** the banner reads "Due today: 10 topics ≈ 30 min" in warning style with the carry-over advice

#### Scenario: Nothing due
- **WHEN** no reviews are due
- **THEN** the banner says nothing is due today and shows no warning

### Requirement: Daily load snapshots
The system SHALL record, once per day for each user with due reviews, the start-of-day `due_topics` and `estimated_minutes`. A scheduled job SHALL take the snapshot daily after missed tasks are marked. If no snapshot exists for today when the load is computed, the system SHALL record one then.

#### Scenario: Scheduled snapshot
- **WHEN** the daily snapshot job runs at 00:03
- **THEN** each user with due reviews gets one snapshot row for today, and running the job again that day does not create duplicates

### Requirement: Review debt detection
Review debt SHALL become active when the snapshots of three consecutive days each exceed the user's debt threshold (default 30 minutes). It SHALL stay active until a later day's load, or today's live estimate, drops below the review budget (default 25 minutes).

The review-load response SHALL include `review_debt_active` and `review_debt_since`.

#### Scenario: Three heavy days
- **WHEN** snapshots for 2026-10-12, 2026-10-13 and 2026-10-14 are 34, 38 and 31 minutes
- **THEN** on 2026-10-14 the response shows `review_debt_active` true and `review_debt_since` 2026-10-12

#### Scenario: Debt clears under budget
- **WHEN** debt is active and today's live estimate falls to 22 minutes after reviewing
- **THEN** the response shows `review_debt_active` false

#### Scenario: A gap breaks the run
- **WHEN** snapshots exceed 30 minutes on 2026-10-12 and 2026-10-14 but 2026-10-13 is 20 minutes
- **THEN** review debt is not active

### Requirement: Review debt warning
While review debt is active, the Dashboard and topic create pages SHALL warn: "Reviews have exceeded N minutes for three days — add no new topics until you are back under M minutes." The warning SHALL NOT block topic creation.

#### Scenario: Creating a topic during debt
- **WHEN** a user opens the topic create page while review debt is active
- **THEN** the page shows the debt warning, and the user can still submit the form successfully

### Requirement: Weekly new-topic cap warning
The review-load response SHALL include:
- `new_topics_this_week`: regular topics, not mistake entries, created since the start of the user's current week;
- `weekly_new_topic_cap`.

The topic create page SHALL show a warning when the count has reached the cap. The warning SHALL NOT block creation.

#### Scenario: Cap reached
- **WHEN** a user with a cap of 8 has created 8 topics this week and opens the create page
- **THEN** the page warns that the weekly new-topic cap is reached, and creating another topic still succeeds

#### Scenario: Mistakes do not count
- **WHEN** a user logs 3 mistake entries this week
- **THEN** `new_topics_this_week` does not include them

### Requirement: Never miss twice prompt
The review-load response SHALL include `never_miss_twice`. It is true when both of these hold:
- the user completed no revision yesterday;
- at least one revision scheduled on or before yesterday is still pending or missed.

When it is true, the Dashboard SHALL show a prompt to do a minimum day of 15–20 minutes of reviews, with a link to the review page.

#### Scenario: Missed yesterday
- **WHEN** a user had reviews due yesterday and completed none
- **THEN** `never_miss_twice` is true and the dashboard shows the minimum-day prompt

#### Scenario: Reviewed yesterday
- **WHEN** a user completed at least one revision yesterday
- **THEN** `never_miss_twice` is false
