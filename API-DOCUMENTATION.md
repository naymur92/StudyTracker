# Study Tracker API Documentation

## Overview

The Study Tracker API is a REST API built with Laravel 12 and Laravel Passport (OAuth 2.0 password grant).

- Base URL: `http://your-domain.com/api`
- Response wrapper: `flag`, `msg`, `data`, `response_code`
- IDs in API payloads are encoded strings (not raw DB integers)

---

## Authentication Flow

### Registration and Email Verification

1. Register using `POST /api/auth/register`
2. User is created with `is_active = false`
3. Verification email is sent with token link
4. Verify with `GET /api/auth/verify-email?email=...&token=...`
5. Account becomes active and verified
6. Login token is only issued for active + verified users

### Headers

Token endpoints (`/api/auth/token`, `/api/auth/token/refresh`):

```http
Accept: application/json
Content-Type: application/json
X-Client-Id: {passport_password_grant_client_id}
X-Client-Secret: {passport_password_grant_client_secret}
```

Register/verify endpoints (`/api/auth/register`, `/api/auth/verify-email`, `/api/auth/resend-verification`):

```http
Accept: application/json
Content-Type: application/json
```

Forgot password endpoints (`/api/auth/forgot-password/request`, `/api/auth/forgot-password/verify`):

```http
Accept: application/json
Content-Type: application/json
```

Protected endpoints (`/api/study/*`, `/api/user`):

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {access_token}
```

---

## Auth Endpoints

### 1. Register

`POST /api/auth/register`

Request body:

```json
{
    "name": "Muhammad Umar",
    "email": "user@example.com",
    "password": "Password@123",
    "password_confirmation": "Password@123"
}
```

Response (201):

```json
{
    "flag": true,
    "msg": "Registration successful. Please verify your email to activate your account.",
    "data": {
        "name": "Muhammad Umar",
        "email": "user@example.com",
        "is_active": false,
        "email_verification_sent": true
    },
    "response_code": 201
}
```

### 2. Verify Email

`GET /api/auth/verify-email?email=user@example.com&token={verification_token}`

Response (200):

```json
{
    "flag": true,
    "msg": "Email verified successfully. Your account is now active.",
    "data": [],
    "response_code": 200
}
```

### 3. Resend Verification Email

`POST /api/auth/resend-verification`

Request body:

```json
{
    "email": "user@example.com"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Verification email sent successfully.",
    "data": [],
    "response_code": 200
}
```

### 4. Get Access Token

`POST /api/auth/token`

Request body:

```json
{
    "email": "user@example.com",
    "password": "Password@123"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Success",
    "token_type": "Bearer",
    "expires_in": 10800,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "def50200abc123...",
    "data": [],
    "response_code": 200
}
```

### 5. Refresh Access Token

`POST /api/auth/token/refresh`

Request body:

```json
{
    "refresh_token": "{your_refresh_token}"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Success",
    "token_type": "Bearer",
    "expires_in": 10800,
    "access_token": "eyJ0eXAi...",
    "refresh_token": "def50200...",
    "data": [],
    "response_code": 200
}
```

### 6. Forgot Password - Request Code

`POST /api/auth/forgot-password/request`

Request body:

```json
{
    "email": "user@example.com"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "If this email exists, a password reset code has been sent.",
    "data": [],
    "response_code": 200
}
```

Notes:

- Password reset code email can be generated only once every 30 minutes per user.
- Code expires in 30 minutes.

### 7. Forgot Password - Verify Code and Reset Password

`POST /api/auth/forgot-password/verify`

Request body:

```json
{
    "email": "user@example.com",
    "code": "123456",
    "password": "NewPassword@123",
    "password_confirmation": "NewPassword@123"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Password reset successful. You can now login with your new password.",
    "data": [],
    "response_code": 200
}
```

Rules:

- A code can be used only once.
- Successful password resets are limited to 5 times per user per month.

---

## User Endpoint

### Get User Profile

`GET /api/user`

Response (200):

```json
{
    "flag": true,
    "msg": "User profile fetched successfully.",
    "data": {
        "id": "a9k31mQz",
        "name": "Muhammad Umar",
        "email": "user@example.com",
        "created_at": "2026-03-27 12:00:00",
        "email_verified_at": "2026-03-27 12:30:00",
        "is_active": true,
        "status": "verified",
        "topics_count": 12,
        "tasks_count": 34,
        "practice_logs_count": 8
    },
    "response_code": 200
}
```

### Update User Profile

`PATCH /api/user`

Request body:

```json
{
    "name": "John Updated"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Profile updated successfully.",
    "data": {
        "id": "a9k31mQz",
        "name": "John Updated",
        "email": "user@example.com",
        "created_at": "2026-03-27 12:00:00",
        "email_verified_at": "2026-03-27 12:30:00",
        "is_active": true,
        "status": "verified"
    },
    "response_code": 200
}
```

Constraint:

- Email cannot be changed via profile update API.

### Change Password (Authenticated)

`POST /api/user/change-password`

Request body:

```json
{
    "current_password": "Password@123",
    "new_password": "NewPassword@123",
    "new_password_confirmation": "NewPassword@123"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Password changed successfully.",
    "data": [],
    "response_code": 200
}
```

---

## Study Endpoints

### Dashboard

- `GET /api/study/dashboard?date=YYYY-MM-DD`
- `GET /api/study/calendar?year=YYYY&month=MM`

### Categories

- `GET /api/study/categories`
- `POST /api/study/categories`
- `PUT/PATCH /api/study/categories/{category}`
- `DELETE /api/study/categories/{category}`

### Topics

- `GET /api/study/topics`
- `POST /api/study/topics`
- `GET /api/study/topics/{topic}`
- `PUT/PATCH /api/study/topics/{topic}`
- `DELETE /api/study/topics/{topic}`

### Study Tasks

- `GET /api/study/daily-tasks`
- `POST /api/study/tasks/{task}/complete`
- `POST /api/study/tasks/{task}/skip`
- `POST /api/study/tasks/{task}/reschedule`

### Practice Logs

- `GET /api/study/practice-logs`
- `POST /api/study/practice-logs`
- `PUT/PATCH /api/study/practice-logs/{practiceLog}`
- `DELETE /api/study/practice-logs/{practiceLog}`

### Revision Templates

Revision templates define the spaced repetition schedule. Users can customize the default system template with their own revision intervals.

- `GET /api/study/revision-templates`
- `PUT /api/study/revision-templates`
- `POST /api/study/revision-templates/reset`

**Reference:** System default revision templates trigger tasks at days: +1, +7, +30, +90 (Ebbinghaus curve).

### Review Queue

- `GET /api/study/review-queue?date=YYYY-MM-DD` (date defaults to today)

The day's question-first review session. Includes revision tasks that are `pending`/`missed` and scheduled on or before the date, from topics that are neither archived nor deleted:

- **one review per topic** — the topic's earliest due revision;
- **ordering** — overdue first (oldest first), then today's reviews interleaved round-robin across categories (alphabetical, uncategorized last);
- **budget split** — each item has `cumulative_minutes` and `within_budget` (the first item is always within budget);
- **grade_preview** — the next review date `again`/`hard`/`good`/`easy` would produce today (`null` = no further review).

```json
{
    "date": "2026-10-14",
    "summary": { "due_topics": 12, "overdue_topics": 2, "per_review_minutes": 3, "estimated_minutes": 36, "budget_minutes": 25, "over_budget": true },
    "items": [
        {
            "task": { "id": "a9k31mQz", "revision_no": 2, "review_kind": "step", "scheduled_date": "2026-10-14", "is_overdue": false },
            "topic": { "id": "b2XpL0qR", "title": "pandas: groupby", "kind": "topic", "lane": "major", "recall_questions": [{ "question": "agg vs transform?", "answer": "..." }], "summary": "...", "category": { "name": "ML", "color": "#4e73df" } },
            "grade_preview": { "again": "2026-10-15", "hard": "2026-10-15", "good": "2026-11-06", "easy": "2027-01-05" },
            "cumulative_minutes": 3,
            "within_budget": true
        }
    ]
}
```

Grade an item with `POST /api/study/tasks/{task}/complete` and `recall_grade` (plus `review_seconds`).

### Review Load

- `GET /api/study/review-load?date=YYYY-MM-DD` (date defaults to today)

Returns the due-today estimate and the soft warnings. The same object is included in the dashboard response as `stats.review_load`.

| Field | Meaning |
| --- | --- |
| `due_topics` / `overdue_topics` | distinct topics with a pending/missed revision on or before the date (archived and deleted topics excluded) |
| `per_review_minutes` | mean of your last 30 timed graded reviews (at least 5, clamped 1–15), otherwise your `minutes_per_review` preference |
| `estimated_minutes` | `due_topics × per_review_minutes`, rounded up |
| `budget_minutes`, `over_budget` | your review budget and whether the estimate exceeds it |
| `review_debt_active`, `review_debt_since` | on after three consecutive daily snapshots above `debt_threshold_minutes`; off once a later day (or today's live estimate) is below the budget |
| `new_topics_this_week`, `weekly_new_topic_cap` | regular topics created since your week started, and your cap |
| `never_miss_twice` | no revision completed yesterday while reviews were due |

All warnings are advisory — nothing is blocked. Daily snapshots come from `study:snapshot-review-load` (00:03) or the first load of the day.

### Review Schedules (presets and category schedules)

- `GET /api/study/schedule-presets` — built-in presets: `standard` [1,7,30,90]; `exam_short` [1,3,7,14] then every 7 days; `long_horizon` [1,3,7,21,60] then every 90 days; `mistakes` [1,3,7]
- `GET /api/study/categories/{category}/schedule` — your schedule for the category, or `schedule: null` with `fallback` (`user_default` / `system_default`)
- `PUT /api/study/categories/{category}/schedule` — set it (demo users get `403`)
- `DELETE /api/study/categories/{category}/schedule` — revert to your default

```json
{
    "preset_key": "exam_short",
    "repeat_until": "2026-12-05",
    "apply_to_existing_topics": true
}
```

A preset fills in whatever you leave out; send `preset_key: "custom"` with `offsets` for your own. Rules: 1–10 strictly increasing offsets (1–3650), `repeat_every_days` 1–365, `repeat_until` today or later and only with a repeat interval. Schedules are per user — a schedule on a system category affects only you. Returns `403` for another user's category.

New topics use the first schedule found: your category schedule → your default revision templates → system defaults. Each topic keeps the schedule it was created with; `apply_to_existing_topics` rewrites the stored schedule of your non-archived topics in that category without moving dates (it takes effect at each topic's next review). Category listings include your `review_schedule` (or `null`).

### Mistakes

- `GET /api/study/mistakes?state=open|active|ready|closed|all&cause=concept|memory|careless&parent_topic_id=` — newest first, paginated; `state` defaults to `open` (active + ready)
- `POST /api/study/mistakes` — log a mistake
- `PATCH /api/study/mistakes/{mistake}` — edit (changing the question updates its title and recall question)
- `DELETE /api/study/mistakes/{mistake}` — delete (its reviews disappear)
- `POST /api/study/mistakes/{mistake}/merge` — close a `ready` mistake; appends its question (with the correct answer) to the parent topic's recall questions unless the parent already has it

```json
{
    "question": "T/F/NG: 'Most respondents…'",
    "my_answer": "False",
    "correct_answer": "Not Given",
    "cause": "concept",
    "source": "IELTS mock 3",
    "parent_topic_id": "b2XpL0qR",
    "logged_on": "2026-10-11"
}
```

Required: `question` (≤500), `correct_answer` (≤2000), `cause`. Optional: `my_answer`, `source` (≤200), `parent_topic_id` (one of your regular topics), `category_id` (defaults to the parent's), `logged_on` (not in the future; default today). A mistake is stored as a topic with `kind: "mistake"`, takes its parent's lane, has no learn task and is reviewed at +1, +3, +7 days with the normal recall grades. States: `active` (reviews pending), `ready` (reviews done), `closed` (merged). Merge returns `422` while still active or when the parent already has 10 recall questions; the response includes `appended_to_parent`. All write routes return `403` for demo users; another user's mistake returns `403`, a regular topic id `404`.

### Weekly Plan

- `GET /api/study/weekly-plan?date=YYYY-MM-DD` — the week containing the date (by your `week_starts_on`): `week_start`, `week_end`, `plan` (or `null`), `blocks` (by date, then slot), `score`, `stats`, `success_threshold_percent`
- `GET /api/study/weekly-plan/history?weeks=8` — your latest plans (1–26), newest first, with their scores
- `POST /api/study/weekly-plan` — `week_start` (must be your week-start weekday; no overlapping plan), `gear` (`green`/`yellow`/`red`), optional `major_focus`, `minor_focus`, `generate_blocks` (default `true`)
- `PATCH /api/study/weekly-plan/{week}` — `gear`, focus fields, `reflection` (≤2000), `if_then_plan` (≤500), `output_note` (≤300); blocks are not changed
- `DELETE /api/study/weekly-plan/{week}`
- `POST /api/study/weekly-plan/{week}/regenerate` — `{ "gear": "yellow" }`: replaces blocks from today on that are still `planned` with that gear's template
- `POST /api/study/weekly-plan/{week}/blocks`, `PATCH /api/study/blocks/{block}`, `DELETE /api/study/blocks/{block}`

Block fields: `block_date` (inside the week), `slot` (`morning`, `class_recap`, `deep`, `block_a`, `block_b`, `review`, `minor`, `other`), `lane` (`major`, `minor`, `review`, `work`), `planned_task` (≤300), `planned_minutes` (5–480), `status` (`planned`, `done`, `partial`, `missed`, `red`), `note` (≤500), optional `category_id`/`topic_id`. `done`, `partial` and `missed` are accepted only for blocks dated today or earlier (`422` otherwise, also when moving a marked block to a later date); future blocks can still be edited, marked `red` or cleared back to `planned`.

Gear templates depend on your `study_profile`. Each day is a workday (office day for a job holder, class day for a student) or an off day (from `off_days`):

| Gear | Job holder: office day / off day | Student: class day / free day |
| --- | --- | --- |
| green | morning 90 + review 20 + minor 20 / block A 150 + block B 75 + review 15 | class recap 30 + deep 90 + review 25 + minor 30 / block A 150 + block B 120 + review 25 |
| yellow | morning 90 + review 15 / review 15, plus block A 150 on the first off day | class recap 20 + deep 60 + review 20 / block A 120 + review 20 |
| red | review 20 every day | review 20 every day |

The weekly plan response also includes `study_profile` and `gear_options` — for each gear: `gear`, `label`, `description` (in your profile's terms), `minutes` (a full week of that template for your profile and off days) and `hours` (rounded). With Friday and Saturday off: job holder 1130 / 705 / 140 minutes, student 1465 / 780 / 140 minutes. Changing the profile only affects blocks generated afterwards (new plans, regeneration).

`score`: counted blocks are non-red blocks dated before today or already marked (a past `planned` block counts as missed); `percent = round(100 × (done + 0.5 × partial) / counted)`, `on_track = percent ≥ success threshold`. Write routes return `403` for demo users and for another user's plan or block.

### Study Preferences

- `GET /api/study/preferences` — the user's preferences merged over defaults
- `PUT /api/study/preferences` — partial update (only sent keys change); demo users get `403`

| Key | Default | Allowed |
| --- | --- | --- |
| `review_budget_minutes` | 25 | 5–180 |
| `review_debt_threshold_minutes` | 30 | 5–240, not below the budget |
| `minutes_per_review` | 3 | 1–30 |
| `weekly_new_topic_cap` | 8 | 1–50 |
| `week_starts_on` | 0 (Sunday) | 0–6 |
| `off_days` | `[5, 6]` (Fri, Sat) | up to 6 distinct values 0–6 |
| `success_threshold_percent` | 80 | 50–100 |
| `study_profile` | `job_holder` | `job_holder` or `student` — selects the weekly gear templates; for a student `off_days` are the days without classes |

### Data Deletion Requests

A user asks for chosen categories of their own data to be deleted. An admin reviews the request in the admin panel (`/admin/data-requests`); on approval the data is archived, the archive is verified, and only then are the rows deleted. The account itself, system categories, login history and activity logs are never deleted.

- `GET /api/study/data-deletion/summary` — every category with its label, description, side-effect `notes`, current `total` and per-record `records` counts (demo users allowed)
- `GET /api/study/data-deletion/requests` — the user's own requests, newest first
- `POST /api/study/data-deletion/requests` — create a request; demo users get `403`
- `GET /api/study/data-deletion/requests/{request}` — one of the user's requests; another user's request returns `404`

| Category key | Deletes |
| --- | --- |
| `topics` | regular topics, their study tasks and practice logs |
| `mistakes` | mistake entries, their study tasks and practice logs |
| `practice_logs` | all practice logs |
| `weekly_plans` | weekly plans and their blocks |
| `categories` | own categories and their review schedules (topics in them become uncategorised) |
| `study_settings` | revision templates, category review schedules; study preferences reset to defaults |
| `report_history` | emailed report records |
| `review_history` | review-load snapshots |

References from rows that stay are cleared, not cascaded: deleting `topics` keeps mistakes (without a parent topic) and weekly blocks (without the topic link).

```json
{
    "categories": ["practice_logs", "review_history"],
    "reason": "Starting over for the next exam",
    "confirmation": "DELETE"
}
```

Rules: `categories` — non-empty array of distinct keys from the table; `reason` — optional, ≤1000; `confirmation` — must be exactly `DELETE`. Only one open request (`pending`, `approved`, `processing` or `failed`) is allowed at a time; a second one returns `422` on `categories`.

Response (`201`):

```json
{
    "flag": true,
    "msg": "Data deletion request submitted. An admin will review it.",
    "data": {
        "id": "xY7kPq2A",
        "reference": "DDR-3F9A1C2B",
        "categories": [
            { "key": "practice_logs", "label": "Practice logs" },
            { "key": "review_history", "label": "Review-load history" }
        ],
        "reason": "Starting over for the next exam",
        "status": "pending",
        "status_label": "Pending review",
        "is_open": true,
        "rejection_reason": null,
        "request_counts": [
            { "key": "practice_logs", "label": "Practice logs", "count": 12 },
            { "key": "review_load_snapshots", "label": "Review-load snapshots", "count": 30 }
        ],
        "deleted_counts": null,
        "created_at": "2026-10-08 10:15:00",
        "reviewed_at": null,
        "completed_at": null,
        "restored_at": null
    },
    "response_code": 201
}
```

Statuses: `pending` → `approved` → `processing` → `completed` (or `failed`, which an admin can retry); `pending`/`failed` → `rejected` (with `rejection_reason`); `completed` → `restored` (an admin restored the archive). `deleted_counts` is filled once completed. Archive location and checksum are never returned to the user.

---

## Encoded ID Notes

All IDs returned by resource-based responses are encoded strings (topics, mistakes, tasks, practice logs, categories, weekly plans and blocks, data deletion requests).

Examples:

- `id: "a9k31mQz"`
- `topic_id: "b2XpL0qR"`
- `category_id: "kL9m2Dq7"`

When sending IDs back to the API (path params or filter/body fields like `topic_id`, `task_id`, `category_id`), send the encoded value from responses.

---

## Common Request Samples

### Create Topic

`POST /api/study/topics`

```json
{
    "category_id": "kL9m2Dq7",
    "title": "Derivatives and Differentiation",
    "description": "Understanding derivatives",
    "source_link": "https://example.com/derivatives",
    "difficulty": "medium",
    "first_study_date": "2026-03-27",
    "notes": "Start with basic rules",
    "tags": ["calculus", "derivatives"],
    "recall_questions": [
        { "question": "What is the derivative of x^n?", "answer": "n·x^(n−1)" },
        { "question": "State the chain rule." }
    ],
    "summary": "Derivative = instantaneous rate of change.\nPower, product, quotient and chain rules.",
    "practice_prompt": "Differentiate 10 mixed functions without notes",
    "lane": "major"
}
```

Recall card fields (create and update): `recall_questions` (up to 10 items; `question` required ≤500 chars, `answer` optional ≤2000), `summary` (≤2000), `practice_prompt` (≤500), `lane` (`major`, `minor` or `work`). The topic's schedule is resolved at creation (category schedule → your default templates → system defaults) and stored on the topic.

`GET /api/study/topics` also accepts `lane` and `kind` (`topic` default, `mistake`, `all`) filters — mistake entries are hidden unless asked for.

### Mark Task Complete

`POST /api/study/tasks/{task}/complete`

```json
{
    "notes": "Answered 4 of 5 questions from memory",
    "recall_grade": "good",
    "review_seconds": 140
}
```

| Field | Rules |
| --- | --- |
| `recall_grade` | optional; `again`, `hard`, `good` or `easy`; only for `revision` tasks (422 otherwise) |
| `review_seconds` | optional; integer 1–3600; stored for revision tasks |
| `difficulty_feedback` | **deprecated** — still accepted (`easy`/`medium`/`hard`), but no longer used; send `recall_grade` instead |

**How a grade moves the schedule.** A topic stores the number of schedule steps passed (`srs_step`). On the review date D:

| Grade | Step | Next review |
| --- | --- | --- |
| `again` | back to 0, `srs_lapses` + 1 | relearn check on D + 1 |
| `hard` | unchanged | relearn check on D + 1 |
| `good` | + 1 | D + gap to the next offset |
| `easy` | + 2 (skips one) | D + gap to the offset after the skipped one |

After the final step, `good`/`easy` schedule a repeat review when the schedule repeats (until its repeat-until date), otherwise the topic graduates with no pending reviews.

After a **graded** revision, the topic's other `pending`/`missed` revisions are re-planned from the review date: rows are re-dated (missed rows become pending), added or removed. Completed and skipped revisions never change. **Note:** a pending review you rescheduled by hand can be moved again by the next graded review.

Completing **without** `recall_grade` keeps the old behavior: the task is completed, the step counts as passed, and no other dates move.

Completing a topic's **learn** task on a day other than its first study date re-plans its revisions from the completion date (only while no revision has been completed yet).

The response `data` is the task resource plus `schedule_outcome` (`null` when nothing was re-planned):

```json
{
    "id": "a9k31mQz",
    "status": "completed",
    "recall_grade": "good",
    "review_seconds": 140,
    "review_kind": "step",
    "schedule_outcome": {
        "srs_step": 2,
        "next_review_date": "2026-11-06",
        "next_review_kind": "step",
        "graduated": false
    }
}
```

### Create Practice Log

`POST /api/study/practice-logs`

```json
{
    "topic_id": "b2XpL0qR",
    "task_id": "x1ZpV88n",
    "practiced_on": "2026-03-27",
    "practice_type": "problem_solving",
    "details": "Solved 10 problems from chapter 5",
    "duration_minutes": 45,
    "outcome": "Got 8/10 correct"
}
```

---

## Response Format

Success:

```json
{
    "flag": true,
    "msg": "Success message",
    "data": {},
    "response_code": 200
}
```

Validation error:

```json
{
    "flag": false,
    "msg": "The email field is required.",
    "errors": {
        "email": ["The email field is required."]
    },
    "data": null,
    "response_code": 422
}
```

---

## Rate Limiting

| Limiter         | Routes                         | Limit  | Scope    |
| --------------- | ------------------------------ | ------ | -------- |
| `auth-register` | `/api/auth/register`           | 5/min  | Per IP   |
| `auth-token`    | `/api/auth/token`              | 8/min  | Per IP   |
| `auth-refresh`  | `/api/auth/token/refresh`      | 20/min | Per IP   |
| `auth-verify`   | verify + resend verification   | 6/min  | Per IP   |
| `auth-forgot`   | forgot password request/verify | 5/min  | Per IP   |
| `study-read`    | `GET /api/study/*`             | 60/min | Per user |
| `study-write`   | `POST/PUT/DELETE /api/study/*` | 30/min | Per user |
| `api-profile`   | `/api/user*`                   | 30/min | Per user |

---

## Resource Field Map (Current)

- `UserResource`: `id`, `name`, `email`, `email_verified_at`, `is_active`, `status`
- `CategoryResource`: `id`, `name`, `color`, `icon`, `user_id`, `is_system`, `topic_count`, `review_schedule` (listings), timestamps
- `TopicResource`: `id`, `user_id`, `category_id`, `title`, `slug`, `description`, `source_link`, `difficulty`, `status`, `first_study_date`, `notes`, `tags`, `kind`, `parent_topic_id`, `recall_questions`, `summary`, `practice_prompt`, `lane`, `srs_step`, `srs_steps_total`, `srs_lapses`, `last_reviewed_on`, `next_review_date` (topic endpoints), `review_schedule` (`offsets`, `repeat_every_days`, `repeat_until`, `source`), `category`, counts, timestamps
- `StudyTaskResource`: `id`, `user_id`, `topic_id`, `title`, `task_type`, `task_type_label`, `revision_no`, `scheduled_date`, `status`, completion/lock flags, `parent_task_id`, `notes`, `difficulty_feedback` (deprecated), `recall_grade`, `review_seconds`, `review_kind` (`step`/`relearn`/`repeat`), `topic`, timestamps
- `PracticeLogResource`: `id`, `user_id`, `topic_id`, `task_id`, `practiced_on`, `practice_type`, `practice_type_label`, `details`, `duration_minutes`, `outcome`, `topic`, `task`, timestamps

---

## Postman Collection

Use the updated collection:

- `StudyTracker-API.postman_collection.json`

Collection includes:

- Auth verification flow (`register`, `verify-email`, `resend-verification`)
- Forgot password flow (`forgot-password/request`, `forgot-password/verify`)
- Encoded ID variables (`category_id`, `topic_id`, `task_id`, `practice_log_id`)
- User profile endpoints (`get`, `patch`, `change-password`)
