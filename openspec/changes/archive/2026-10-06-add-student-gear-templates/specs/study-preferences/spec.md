# Spec Delta

## ADDED Requirements

### Requirement: Study profile preference
The preferences SHALL include `study_profile`: `job_holder` or `student`. It decides which gear templates weekly plans use. For a student, `off_days` means the days without classes.

#### Scenario: Switch to the student profile
- **WHEN** a user sends `study_profile` = `student`
- **THEN** the response returns `study_profile` = `student`, and plans generated afterwards use the student gear templates

#### Scenario: Existing user keeps today's behavior
- **WHEN** a user who never saved `study_profile` reads their preferences
- **THEN** `study_profile` is `job_holder`

### Requirement: Profile selector in the UI
The preferences form SHALL let the user choose between "Job holder" and "Student". It SHALL label the off-days field according to the chosen profile: "Off days (no office)" for a job holder, "Days without classes" for a student.

#### Scenario: Choosing Student
- **WHEN** a user selects Student in the preferences form
- **THEN** the off-days field is labelled "Days without classes", and saving stores `study_profile` = `student`

## MODIFIED Requirements

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
| `study_profile` | `job_holder` |

#### Scenario: Defaults applied
- **WHEN** preferences are read for a user without saved values
- **THEN** `review_budget_minutes` is 25, `week_starts_on` is 0, `off_days` is [5, 6] and `study_profile` is `job_holder`

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
| `study_profile` | `job_holder` or `student` |

#### Scenario: Threshold below budget
- **WHEN** a user sets `review_debt_threshold_minutes` = 20 while the budget is 25
- **THEN** the API responds 422 with a validation error on `review_debt_threshold_minutes`

#### Scenario: Invalid off day
- **WHEN** a user sends `off_days` = [5, 9]
- **THEN** the API responds 422 with a validation error on `off_days`

#### Scenario: Unknown profile
- **WHEN** a user sends `study_profile` = `teacher`
- **THEN** the API responds 422 with a validation error on `study_profile`
