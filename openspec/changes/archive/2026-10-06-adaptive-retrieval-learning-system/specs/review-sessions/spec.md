# Spec Delta

## Purpose

Turn the day's due reviews into a short, mixed, question-first self-test session. Each answer is attempted from memory before it is revealed and graded, and the session stays within the user's daily review budget.

## ADDED Requirements

### Requirement: Review queue endpoint
`GET /api/study/review-queue` SHALL accept an optional `date` (default: today in the application timezone). It SHALL return the user's revision tasks that are `pending` or `missed`, scheduled on or before that date, from topics that are neither archived nor deleted.

The response SHALL contain a `summary` and an ordered `items` list. The route uses the study read rate limiter.

#### Scenario: Due and future reviews
- **WHEN** a user has one revision due today, one overdue and one scheduled tomorrow, and requests the queue for today
- **THEN** the queue contains the overdue and the due-today reviews but not tomorrow's

#### Scenario: Archived topic excluded
- **WHEN** a revision is due today for an archived topic
- **THEN** the queue does not include it

### Requirement: One review per topic
The queue SHALL include at most one item per topic: the topic's earliest due revision task. Summary counts SHALL count topics, not tasks.

#### Scenario: Two overdue revisions of one topic
- **WHEN** a topic has Revision 2 and Revision 3 both overdue
- **THEN** the queue contains only Revision 2 for that topic, and the summary counts the topic once

### Requirement: Queue ordering
The queue SHALL put overdue items first, ordered by scheduled date with the oldest first. The items due on the requested date SHALL follow, interleaved round-robin across categories:
- categories are taken in alphabetical order by name, with uncategorized topics last;
- within a category, items keep scheduled-date order.

#### Scenario: Overdue before today
- **WHEN** the queue has an item overdue since 2026-10-01, one overdue since 2026-10-04, and items due today
- **THEN** the 2026-10-01 item comes first, then the 2026-10-04 item, then today's items

#### Scenario: Today's reviews are mixed across categories
- **WHEN** today's due items are A1 and A2 from category "Algorithms" and B1 from category "Backend"
- **THEN** they are ordered A1, B1, A2

### Requirement: Queue summary and budget split
The queue `summary` SHALL contain:
- `due_topics`
- `overdue_topics`
- `per_review_minutes`
- `estimated_minutes`
- `budget_minutes`
- `over_budget`

Each item SHALL carry `cumulative_minutes`, and `within_budget` = true while `cumulative_minutes` does not exceed the budget. The first item is always within budget.

#### Scenario: Queue larger than the budget
- **WHEN** 12 topics are due, the per-review estimate is 3 minutes and the budget is 25 minutes
- **THEN** the summary shows `estimated_minutes` 36 and `over_budget` true, and the first 8 items are marked `within_budget`

### Requirement: Grade preview per item
Each queue item SHALL include `grade_preview`: the next review date that `again`, `hard`, `good` and `easy` would each produce for that topic today, following the scheduling rules. A grade after which no review would be scheduled shows null.

#### Scenario: Preview for a mid-schedule topic
- **WHEN** a topic with offsets [1, 7, 30, 90] and `srs_step` 1 is in today's queue on 2026-10-14
- **THEN** its preview shows `again` and `hard` = 2026-10-15, `good` = 2026-11-06 and `easy` = 2027-01-05

#### Scenario: Preview at graduation
- **WHEN** a topic without a repeat rule is on its final step
- **THEN** its preview shows null for `good` and `easy`

### Requirement: Queue item content
Each queue item SHALL include:
- the task's `id`, `review_kind`, `revision_no`, `scheduled_date` and an overdue flag;
- the topic's `id`, `title`, `kind`, `lane`, `recall_questions`, `summary`, `practice_prompt` and `source_link`;
- the category name and color;
- the mistake details, for mistake entries.

#### Scenario: Mistake entry in the queue
- **WHEN** a mistake entry's review is due
- **THEN** its queue item has `kind` = `mistake` and includes the wrong answer, the correct answer and the cause

### Requirement: Question-first review page
The SPA SHALL provide a review page that shows one queue item at a time. The page SHALL:
- show the topic title, category, lane and recall questions;
- hide answers and the summary until the user chooses Reveal;
- enable the four grade buttons only after Reveal, each labelled with its previewed next date;
- support keyboard shortcuts: Space to reveal, 1–4 to grade.

#### Scenario: Reveal then grade
- **WHEN** a user opens the review page, reads the questions, presses Reveal and then presses Good
- **THEN** the answers and summary appear only after Reveal, the revision is completed with `recall_grade` = `good`, and the next card is shown

#### Scenario: Grading before reveal
- **WHEN** a user tries to grade a card before revealing it
- **THEN** the grade buttons are disabled and nothing is submitted

### Requirement: Blank-page recall when a topic has no questions
For a topic without recall questions, the review page SHALL ask the user to recall everything they remember about the topic before revealing. It SHALL still allow grading, and SHALL offer a link to add questions to the topic.

#### Scenario: Topic without questions
- **WHEN** the current card's topic has no recall questions
- **THEN** the page shows a blank-page recall prompt, Reveal and grading still work, and an "Add questions" link opens the topic's edit page

### Requirement: Review timing capture
The review page SHALL measure the seconds from showing a card to submitting its grade, capped at 3600. It SHALL send the value as `review_seconds` with the grade.

#### Scenario: Timed review
- **WHEN** a user spends about two minutes on a card before grading
- **THEN** the completion request includes `review_seconds` of about 120

### Requirement: Relearn and mistake prompts after a failed recall
After an `again` or `hard` grade, the review page SHALL:
- show a relearn note telling the user to re-study the answer key now (about 5 minutes) and that the topic returns tomorrow;
- offer a "Log a mistake" action that opens the mistake form linked to that topic.

#### Scenario: Failed recall
- **WHEN** a user grades a card `again`
- **THEN** the page shows the relearn note with tomorrow's date and a "Log a mistake" action with the parent topic pre-filled

### Requirement: Budget notice during a session
When the session's cumulative estimated time passes the review budget, the review page SHALL tell the user. The user can stop, leaving the remaining reviews due, or continue.

#### Scenario: Budget reached
- **WHEN** the next card is the first one not `within_budget`
- **THEN** the page tells the user they have reached their daily review budget and offers Stop and Continue

### Requirement: Session summary
When the queue is finished or the user stops, the review page SHALL show:
- the number of reviews completed;
- the total review time;
- the count for each grade;
- the number of reviews still due.

#### Scenario: Finishing a session
- **WHEN** a user grades 8 cards (6 good, 1 hard, 1 easy) and stops with 4 remaining
- **THEN** the summary shows 8 reviewed, the total time, the grade counts and "4 still due"

### Requirement: Grading from the agenda
On the Dashboard and Daily Tasks pages, completing a revision task SHALL ask for a recall grade (Again, Hard, Good or Easy) before submitting. Completing a learn or practice task SHALL submit without a grade, and no hard-coded difficulty value SHALL be sent.

#### Scenario: Checking off a revision on the Daily Tasks page
- **WHEN** a user checks a revision task on the Daily Tasks page
- **THEN** a grade picker appears, and the task is completed only after a grade is chosen

#### Scenario: Checking off a learn task
- **WHEN** a user checks a learn task
- **THEN** it is completed without a grade

### Requirement: Review entry points
The SPA SHALL add a "Review" navigation item. The Dashboard and Daily Tasks pages SHALL link to the review page from the due-today estimate.

#### Scenario: Starting a review from the dashboard
- **WHEN** a user with due reviews selects "Start review" on the dashboard
- **THEN** the review page opens with today's queue

### Requirement: Daily agenda ordering with extended revision numbers
The daily agenda SHALL keep its existing group keys. The groups SHALL be ordered:
1. `learn`
2. `revision_N`, ascending by N for any N
3. `practice`
4. `overdue`

#### Scenario: Revision numbers above four
- **WHEN** a day has tasks numbered Revision 2 and Revision 6
- **THEN** the agenda lists `learn`, `revision_2`, `revision_6`, `practice` and `overdue` in that order, skipping empty groups
