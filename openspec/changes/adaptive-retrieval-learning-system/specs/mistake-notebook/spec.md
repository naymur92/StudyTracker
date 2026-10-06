# Spec Delta

## Purpose

Capture every wrong answer as a short-interval review item, from a mock, an exercise or an interview question. Each entry is fixed while fresh, then folded back into the topic it belongs to: the "mistake notebook" from the learning-science guide.

## ADDED Requirements

### Requirement: Log a mistake
`POST /api/study/mistakes` SHALL create a mistake entry from three required fields:
- `question`, ≤500 characters;
- `correct_answer`, ≤2000 characters;
- `cause`: `concept`, `memory` or `careless`.

The route uses the study write rate limiter and rejects demo users with 403.

#### Scenario: Log a mistake from a mock test
- **WHEN** a user logs the question "T/F/NG: 'Most respondents…'" with `correct_answer` "Not Given" and `cause` "concept"
- **THEN** the API responds 201 with the new mistake entry

#### Scenario: Missing cause
- **WHEN** a user logs a mistake without `cause`
- **THEN** the API responds 422 with a validation error on `cause`

#### Scenario: Demo user
- **WHEN** the demo user logs a mistake
- **THEN** the API responds 403 and nothing is created

### Requirement: Optional mistake context
Logging a mistake SHALL also accept these optional fields:

| Field | Rules |
| --- | --- |
| `my_answer` | ≤2000 characters |
| `source` | ≤200 characters |
| `parent_topic_id` | one of the user's own regular topics |
| `category_id` | defaults to the parent's category |
| `logged_on` | not in the future; defaults to today |

An entry with a parent topic SHALL take the parent's lane.

#### Scenario: Mistake linked to a parent topic
- **WHEN** a user logs a mistake with `my_answer` "False", `source` "IELTS mock 3" and an IELTS parent topic in the minor lane
- **THEN** the entry stores the wrong answer and the source, and takes the parent's category and the minor lane

#### Scenario: Parent topic of another user
- **WHEN** a user sets `parent_topic_id` to a topic they do not own
- **THEN** the API responds 422 with a validation error on `parent_topic_id`

#### Scenario: Future log date
- **WHEN** a user sets `logged_on` to tomorrow
- **THEN** the API responds 422 with a validation error on `logged_on`

### Requirement: Mistake entries are reviewable topics
A mistake entry SHALL be stored as a topic of kind `mistake`:
- its title is taken from the question;
- its single recall question is the question, with the correct answer as its answer;
- it has no learn task.

It SHALL use the `mistakes` schedule (+1, +3 and +7 days from `logged_on`) and follow the same recall-grade rules as other topics.

#### Scenario: Reviews created
- **WHEN** a mistake is logged on 2026-10-11
- **THEN** it has pending revisions on 2026-10-12, 2026-10-14 and 2026-10-18 and no learn task

#### Scenario: Failed mistake review
- **WHEN** a mistake's review is graded `again`
- **THEN** its schedule restarts with a relearn check the next day, as for any topic

### Requirement: Mistake states
Every mistake entry SHALL be in exactly one state:
- `active`: it has a pending or missed review;
- `ready`: it has no pending or missed review and is not closed;
- `closed`: it has been merged or closed.

#### Scenario: Graduated mistake
- **WHEN** a mistake's third review is graded `good`
- **THEN** its state becomes `ready`

### Requirement: List mistakes
`GET /api/study/mistakes` SHALL return the user's mistake entries, newest first and paginated.

It SHALL accept these filters:
- `state`: `open` (active and ready; the default), `active`, `ready`, `closed` or `all`;
- `cause`;
- `parent_topic_id`.

Each entry SHALL include:
- the question, the wrong answer, the correct answer, the cause and the source;
- `logged_on`;
- the parent topic's id and title, the category and the lane;
- the state, `next_review_date` and `srs_step`.

#### Scenario: Default list
- **WHEN** a user with 2 active, 1 ready and 4 closed mistakes lists mistakes without filters
- **THEN** the 3 open entries are returned

#### Scenario: Filter by cause
- **WHEN** a user filters with `cause` = `careless`
- **THEN** only careless mistakes are returned

### Requirement: Update and delete a mistake
`PATCH /api/study/mistakes/{mistake}` SHALL update any of the log fields. A changed question SHALL also update the entry's title and recall question. `DELETE` SHALL delete the entry so that its reviews no longer appear as due work.

Both routes respond 404 when the target is not a mistake entry, 403 when the entry belongs to another user, and 403 for demo users.

#### Scenario: Correct the answer text
- **WHEN** a user edits a mistake's `correct_answer`
- **THEN** the entry's recall-question answer shows the new text in later reviews

#### Scenario: Regular topic id
- **WHEN** a user calls the mistake update endpoint with the id of a regular topic
- **THEN** the API responds 404

### Requirement: Merge closes a ready mistake
`POST /api/study/mistakes/{mistake}/merge` SHALL work only on an entry in state `ready`, and responds 422 otherwise. A successful merge SHALL close the entry. The response SHALL say whether a question was added to the parent topic. The route rejects demo users with 403.

#### Scenario: Merge while still active
- **WHEN** a user merges a mistake that still has a pending review
- **THEN** the API responds 422 and nothing changes

#### Scenario: Successful merge closes the entry
- **WHEN** a user merges a `ready` mistake
- **THEN** its state becomes `closed`, and it no longer appears in the default mistake list

### Requirement: Merge adds the question to the parent topic
When a mistake with an existing parent topic is merged, the system SHALL append its question, with the correct answer, to the parent's recall questions. A question the parent already has, compared case-insensitively and ignoring surrounding whitespace, is not appended again.

A parent that already holds 10 recall questions SHALL make the merge fail with a 422 response.

#### Scenario: Merge into the parent
- **WHEN** a user merges a `ready` mistake whose parent topic has 4 recall questions
- **THEN** the parent has 5 recall questions, the last one being the mistake's question and answer

#### Scenario: Question already present
- **WHEN** the parent already has the same question with different capitalisation
- **THEN** the merge closes the mistake without adding a duplicate question

#### Scenario: Parent already full
- **WHEN** the parent topic already has 10 recall questions
- **THEN** the API responds 422, says the parent has the maximum number of questions, and the mistake stays `ready`

### Requirement: Merge without a parent closes the entry
When a `ready` mistake has no parent topic, or its parent was deleted, merging SHALL close it without changing any other topic.

#### Scenario: No parent
- **WHEN** a user merges a `ready` mistake without a parent topic
- **THEN** the mistake becomes `closed`, and the response reports that nothing was appended

### Requirement: Mistakes page
The SPA SHALL provide a Mistakes page that:
- lists entries with state and cause filters;
- has a form to log a new mistake, including a parent topic picker;
- shows the next review date of each entry;
- offers "Merge into <parent>", or "Close" when there is no parent, on `ready` entries.

#### Scenario: Logging from the Mistakes page
- **WHEN** a user fills in the log form with a parent topic and saves
- **THEN** the new entry appears in the list as `active` with its first review date tomorrow

#### Scenario: Merging from the list
- **WHEN** a user selects "Merge into <parent>" on a ready entry
- **THEN** the entry moves to the closed list, and the parent topic shows the new recall question
