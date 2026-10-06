# Spec Delta

## Purpose

Store each user's personal study rules: review budget, debt threshold, time per review, weekly new-topic cap, week layout and success line. Review-load warnings and weekly planning follow these settings instead of fixed constants.

## ADDED Requirements

### Requirement: Read study preferences
`GET /api/study/preferences` SHALL return all of the user's study preferences, with defaults for any value the user has not set. The route uses the study read rate limiter.

#### Scenario: New user preferences
- **WHEN** a user who has never saved preferences requests them
- **THEN** the response contains every preference with its default value

### Requirement: Preference defaults
The defaults SHALL be:

| Preference | Default |
| --- | --- |
| `review_budget_minutes` | 25 |
| `review_debt_threshold_minutes` | 30 |
| `minutes_per_review` | 3 |
| `weekly_new_topic_cap` | 8 |
| `week_starts_on` | 0 (Sunday) |
| `off_days` | [5, 6] (Friday and Saturday) |
| `success_threshold_percent` | 80 |

#### Scenario: Defaults applied
- **WHEN** preferences are read for a user without saved values
- **THEN** `review_budget_minutes` is 25, `week_starts_on` is 0 and `off_days` is [5, 6]

### Requirement: Update study preferences
`PUT /api/study/preferences` SHALL accept any subset of the preferences and save only the keys it receives. Other values stay unchanged. The response SHALL return the full, merged set. The route uses the study write rate limiter and rejects demo users with 403.

#### Scenario: Partial update
- **WHEN** a user sends only `review_budget_minutes` = 20
- **THEN** the budget becomes 20 and every other preference keeps its previous value

#### Scenario: Demo user
- **WHEN** the demo user submits a preferences update
- **THEN** the API responds 403 and nothing is saved

### Requirement: Preference validation
The system SHALL reject a preferences update with a 422 response if any value is outside these limits:

| Preference | Allowed values |
| --- | --- |
| `review_budget_minutes` | 5–180 |
| `review_debt_threshold_minutes` | 5–240, and not below the budget |
| `minutes_per_review` | 1–30 |
| `weekly_new_topic_cap` | 1–50 |
| `week_starts_on` | 0–6 |
| `off_days` | 0–6 distinct values, each from 0 to 6 |
| `success_threshold_percent` | 50–100 |

#### Scenario: Threshold below budget
- **WHEN** a user sets `review_debt_threshold_minutes` = 20 while the budget is 25
- **THEN** the API responds 422 with a validation error on `review_debt_threshold_minutes`

#### Scenario: Invalid off day
- **WHEN** a user sends `off_days` = [5, 9]
- **THEN** the API responds 422 with a validation error on `off_days`

### Requirement: Preferences in the UI
The settings page SHALL show the preferences in a form with their current values and defaults. It SHALL save them through the update endpoint and show validation errors next to the fields.

#### Scenario: Changing the review budget
- **WHEN** a user changes the review budget to 20 minutes and saves
- **THEN** the dashboard's due-today banner uses 20 minutes as the budget from then on
