# study-topics Specification

## Purpose

Make each topic a self-test card. A topic stores recall questions, an answer-key summary, a practice prompt and the lane it belongs to, so every review can be a test rather than a re-read.

## Requirements

### Requirement: Recall questions on a topic
Creating and updating a topic SHALL accept `recall_questions`: a list of up to 10 items. Each item has a required `question` of at most 500 characters and an optional `answer` of at most 2000 characters. The topic resource SHALL return the list in its saved order; the default is an empty list.

#### Scenario: Save recall questions
- **WHEN** a user creates a topic with three recall questions, one of them with an answer
- **THEN** the topic resource returns the three questions in the same order, with the answer on the one that had it

#### Scenario: Too many questions
- **WHEN** a user submits 11 recall questions
- **THEN** the API responds 422 with a validation error on `recall_questions`

#### Scenario: Question missing text
- **WHEN** a user submits a recall-question item with an empty `question`
- **THEN** the API responds 422 and identifies the failing item

### Requirement: Topic summary as answer key
Creating and updating a topic SHALL accept an optional `summary` of at most 2000 characters. The topic resource SHALL return it.

#### Scenario: Save a summary
- **WHEN** a user saves a five-line summary on a topic
- **THEN** the topic resource returns the summary with its line breaks preserved

### Requirement: Practice prompt
Creating and updating a topic SHALL accept an optional `practice_prompt` of at most 500 characters: a task such as an exercise, a re-implementation or a diagram to redraw. The topic resource SHALL return it.

#### Scenario: Save a practice prompt
- **WHEN** a user sets the practice prompt "Re-implement groupby/agg on a toy dataset"
- **THEN** the topic resource returns that prompt

### Requirement: Topic lane
Creating and updating a topic SHALL accept an optional `lane`: `major`, `minor` or `work`. Any other value SHALL be rejected with a 422 response. The topic resource SHALL return the lane, or null.

#### Scenario: Set a lane
- **WHEN** a user creates a topic with `lane` = `work`
- **THEN** the topic resource returns `lane` = `work`

#### Scenario: Invalid lane
- **WHEN** a user submits `lane` = `review`
- **THEN** the API responds 422 with a validation error on `lane`

### Requirement: Topic list filters by lane and kind
`GET /api/study/topics` SHALL accept a `lane` filter and a `kind` filter (`topic`, `mistake` or `all`). Without `kind`, the list SHALL return only regular topics, not mistake entries. The topic resource SHALL return `kind`.

#### Scenario: Default list hides mistakes
- **WHEN** a user with 5 topics and 3 mistake entries lists topics without filters
- **THEN** the response contains the 5 topics only

#### Scenario: Filter by lane
- **WHEN** a user lists topics with `lane` = `minor`
- **THEN** only topics in the minor lane are returned

### Requirement: Deleted topics leave no due work
Tasks whose topic has been deleted SHALL NOT appear in any of these:
- the daily agenda
- the dashboard counts
- the calendar counts
- the review queue
- review-load estimates

#### Scenario: Topic deleted with pending revisions
- **WHEN** a user deletes a topic that has revisions due today
- **THEN** those revisions no longer appear in today's agenda, the review queue or the due-today estimate

### Requirement: Recall card editing in the UI
The topic create and edit pages SHALL let the user:
- add, edit, reorder and remove recall questions, with optional answers;
- write a summary;
- set a practice prompt;
- choose a lane.

The pages SHALL show a hint that 3–5 questions work best.

#### Scenario: Add questions while creating a topic
- **WHEN** a user adds three questions and a summary on the create page and saves
- **THEN** the new topic's detail page shows the three questions and the summary

### Requirement: Recall card on the topic detail page
The topic detail page SHALL show:
- the lane;
- the recall questions, with answers hidden until the user expands them;
- the summary and the practice prompt;
- the topic's schedule (offsets and repeat rule), current step, lapse count and next review date.

#### Scenario: Viewing a topic
- **WHEN** a user opens a topic with recall questions
- **THEN** the questions are visible, each answer is hidden until expanded, and the next review date and step progress (for example "Step 2 of 4") are shown
