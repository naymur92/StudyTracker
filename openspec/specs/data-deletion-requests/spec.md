# data-deletion-requests Specification

## Purpose

Let users ask, from their own panel, for chosen categories of their study data to be deleted. Admins review each request against a live summary of the data, then approve or reject it. Approved deletions run only after the data has been archived.

## Requirements

### Requirement: Deletable data categories
A request SHALL name one or more of eight category keys. Four cover study content and four cover organisation and history, as defined in the next two requirements. A category SHALL cover only the requesting user's own rows. It SHALL include rows the user had already soft-deleted, and SHALL remove them permanently.

#### Scenario: Previously soft-deleted rows are included
- **WHEN** a request for `categories` is carried out and the user has one active and one soft-deleted category
- **THEN** both categories are permanently removed

#### Scenario: Only the requester's rows
- **WHEN** a request for `practice_logs` is carried out
- **THEN** other users' practice logs are unchanged

### Requirement: Study content categories
The study content categories SHALL delete:

| Key | Deletes |
| --- | --- |
| `topics` | regular topics, their study tasks and practice logs |
| `mistakes` | mistake entries, their study tasks and practice logs |
| `practice_logs` | all practice logs |
| `weekly_plans` | weekly plans and their blocks |

#### Scenario: Topics include their tasks and logs
- **WHEN** a request for `topics` is carried out for a user with 4 topics, 20 tasks on them and 6 practice logs on them
- **THEN** those 4 topics, 20 tasks and 6 practice logs are deleted, and the user's mistakes and weekly plans are untouched

#### Scenario: Weekly plans include blocks
- **WHEN** a request for `weekly_plans` is carried out for a user with 2 plans and 9 blocks
- **THEN** the 2 plans and 9 blocks are deleted

### Requirement: Organisation and history categories
The organisation and history categories SHALL delete:

| Key | Deletes |
| --- | --- |
| `categories` | own categories and their review schedules |
| `study_settings` | revision templates, category review schedules; resets study preferences to defaults |
| `report_history` | emailed report records |
| `review_history` | review-load snapshots |

#### Scenario: Study settings reset
- **WHEN** a request for `study_settings` is carried out
- **THEN** the user's revision templates and category review schedules are deleted, and their study preferences are back to the defaults

### Requirement: Data that is never deleted by a request
A request SHALL never delete:
- the user account or its profile fields, other than `study_preferences` under `study_settings`;
- system categories and system revision templates (no owner);
- other users' data;
- login history or activity logs.

#### Scenario: System categories survive
- **WHEN** a request for `categories` is carried out
- **THEN** every category with no owner still exists, and so do review schedules other users set on it

#### Scenario: Audit trail survives
- **WHEN** any request is carried out
- **THEN** the user's activity logs and login history are unchanged

### Requirement: References to deleted rows are cleared, not cascaded
When a deleted row is referenced by a row the request does not delete, the reference SHALL be set to null and the referencing row SHALL be kept. Examples: a mistake's parent topic, a weekly block's topic or category, a topic's category.

#### Scenario: Deleting categories leaves topics uncategorised
- **WHEN** a request for `categories` only is carried out and the user has topics in those categories
- **THEN** the topics still exist with no category

#### Scenario: Deleting topics keeps mistakes
- **WHEN** a request for `topics` only is carried out and a mistake's parent topic is among them
- **THEN** the mistake still exists with no parent topic

### Requirement: User data summary
`GET /api/study/data-deletion/summary` SHALL return, for each category, its key, label, short description, the number of rows it would delete right now (in total and per kind of record), and the side-effect notes shown before confirming. The route SHALL use the `study-read` limiter and SHALL be available to demo users.

#### Scenario: Summary counts
- **WHEN** a user with 3 topics, 1 mistake and 2 weekly plans requests the summary
- **THEN** `topics` reports 3 topics, `mistakes` reports 1 mistake and `weekly_plans` reports 2 plans, each with its own task, log and block counts

### Requirement: Create a deletion request
`POST /api/study/data-deletion/requests` SHALL create a `pending` request from these fields:
- `categories`: a non-empty array of distinct category keys;
- `reason`: optional, ≤1000 characters;
- `confirmation`: must equal `DELETE`.

The response SHALL be 201 with the request. The request SHALL store the per-category counts at the moment it was made. The route SHALL use the `study-write` limiter.

#### Scenario: Valid request
- **WHEN** a user submits `categories` ["practice_logs", "review_history"] with `confirmation` "DELETE"
- **THEN** the API responds 201 with a `pending` request listing both categories and their current counts

#### Scenario: Unknown category
- **WHEN** a user submits `categories` ["topics", "passwords"]
- **THEN** the API responds 422 with a validation error on `categories`

#### Scenario: Missing confirmation
- **WHEN** a user submits a valid category list with `confirmation` "delete me"
- **THEN** the API responds 422 with a validation error on `confirmation` and no request is created

### Requirement: One open request per user
A user SHALL have at most one open request, where open means `pending`, `approved`, `processing` or `failed`. A new request made while one is open SHALL be refused with 422.

#### Scenario: Second request while pending
- **WHEN** a user with a `pending` request submits another one
- **THEN** the API responds 422 saying a request is already in progress

#### Scenario: New request after rejection
- **WHEN** a user whose only request was `rejected` submits a new one
- **THEN** the API responds 201

### Requirement: Demo users cannot request deletion
The create endpoint SHALL reject the demo user with 403 and create nothing.

#### Scenario: Demo user
- **WHEN** the demo user submits a deletion request
- **THEN** the API responds 403 and no request exists

### Requirement: Users see their own requests
`GET /api/study/data-deletion/requests` SHALL list the user's own requests, newest first. `GET /api/study/data-deletion/requests/{request}` SHALL show one of them. Each request SHALL show its hashed ID, categories, reason, status, rejection reason, request-time counts, deleted counts once completed, and timestamps. Another user's request SHALL return 404.

#### Scenario: Rejected request shows the reason
- **WHEN** a user opens a request an admin rejected with the reason "Please export your notes first"
- **THEN** the response shows status `rejected` and that reason

#### Scenario: Someone else's request
- **WHEN** a user requests another user's request by its hashed ID
- **THEN** the API responds 404

### Requirement: Request form and confirmation prompt in the user panel
The Profile page SHALL have a "Delete my data" section. It SHALL list every category with its label, description and current count, and allow ticking one or more of them. Submitting SHALL open a confirmation prompt that names each chosen category with its counts and side-effect notes and says that an admin will review the request. The prompt SHALL send nothing until the user types `DELETE`.

#### Scenario: Confirm prompt lists consequences
- **WHEN** a user ticks `categories` and selects "Request deletion"
- **THEN** a prompt shows the category count and the note that topics in those categories will be left uncategorised, and the confirm button stays disabled until `DELETE` is typed

#### Scenario: Cancel the prompt
- **WHEN** the user closes the prompt without confirming
- **THEN** no request is sent

#### Scenario: Open request blocks the form
- **WHEN** the user already has an open request
- **THEN** the section shows that request's status instead of an enabled form

### Requirement: Request history in the user panel
The "Delete my data" section SHALL list the user's requests, with categories, status, date requested, and either the rejection reason or the completion date. The demo user SHALL see the section with the form disabled and a note that the demo account cannot request deletion.

#### Scenario: Completed request
- **WHEN** a user's request has been carried out
- **THEN** the list shows it as completed, with its completion date and deleted counts

### Requirement: Pending requests on the admin dashboard
The admin dashboard SHALL show a count of pending requests and the five oldest pending requests, each linking to its detail page. The admin sidebar SHALL have a "Data Requests" item with a pending-count badge. Admins without `data-request-list` SHALL see neither.

#### Scenario: New request appears
- **WHEN** a user submits a request and an admin with `data-request-list` opens the dashboard
- **THEN** the pending count includes it and it appears in the pending list

### Requirement: Admin request list
`/admin/data-requests` SHALL list requests, newest first and paginated. Admins SHALL be able to filter by status and search by user name or email. Each row SHALL show the reference, user, categories, status, requested date and reviewer. The page SHALL require `data-request-list`.

#### Scenario: Filter by status
- **WHEN** an admin filters the list by `pending`
- **THEN** only pending requests are listed

#### Scenario: Missing permission
- **WHEN** an admin without `data-request-list` opens the list
- **THEN** access is denied with 403

### Requirement: Admin request detail
The detail page SHALL show:
- the user (name, email, type, link to their study report);
- the categories, reason and status timeline (requested, reviewed, processed, restored, with who acted);
- the request-time counts;
- any rejection reason or error;
- after completion: deleted counts and archive details.

It SHALL require `data-request-view`.

#### Scenario: Open a pending request
- **WHEN** an admin with `data-request-view` opens a pending request
- **THEN** the page shows the user, the chosen categories, the reason and the request-time counts

### Requirement: Live data summary for a request
While a request is open, its detail page SHALL show a live summary for its categories:
- current row counts per kind of record;
- the earliest and latest date in each kind;
- up to 20 sample titles for topics, mistakes and categories;
- the references that would be cleared.

Counts that differ from the request-time counts SHALL be highlighted.

#### Scenario: Data changed since the request
- **WHEN** the user adds 2 topics after requesting deletion of `topics`, and an admin opens the request
- **THEN** the live summary shows 2 more topics than the request-time count, with that difference highlighted

### Requirement: Approve a request
An admin with `data-request-approve` SHALL be able to approve a `pending` request after confirming. Approval SHALL record the reviewer and time, set status `approved`, and start the deletion in the background. Approving a request that is not pending SHALL be refused.

#### Scenario: Approve
- **WHEN** an admin approves a pending request and confirms
- **THEN** the request becomes `approved`, the reviewer is recorded and the deletion is started

#### Scenario: Approve twice
- **WHEN** an admin approves a request that is already `completed`
- **THEN** the action is refused with an error and nothing changes

### Requirement: Reject a request
An admin with `data-request-approve` SHALL be able to reject a `pending` or `failed` request with a required reason of at most 1000 characters. Rejection SHALL record the reviewer, time and reason, set status `rejected`, and delete nothing.

#### Scenario: Reject with reason
- **WHEN** an admin rejects a pending request with the reason "Duplicate request"
- **THEN** the request becomes `rejected` with that reason, and all of the user's data remains

#### Scenario: Reject without reason
- **WHEN** an admin rejects without giving a reason
- **THEN** the form shows a validation error and the request stays `pending`

### Requirement: Carry out an approved deletion
An approved request SHALL move to `processing`. The system SHALL then:
1. collect the affected rows;
2. archive them and verify the archive;
3. in one transaction, delete exactly the archived rows and clear the archived references.

On success the request SHALL be `completed`, with per-table deleted counts and the completion time. Rows created after collection SHALL be untouched.

#### Scenario: Successful deletion
- **WHEN** an approved request for `practice_logs` is processed for a user with 12 practice logs
- **THEN** an archive holding the 12 logs exists, the 12 logs are gone, and the request is `completed` with a deleted count of 12

#### Scenario: Nothing is deleted before the archive is verified
- **WHEN** writing or verifying the archive fails
- **THEN** none of the user's data is deleted

### Requirement: Failed deletions and retry
If any step fails, the transaction SHALL be rolled back, any partial archive file removed, and the request set to `failed` with the error recorded. A failed deletion SHALL never be retried automatically. An admin with `data-request-approve` SHALL be able to retry it, which starts processing again from step 1.

#### Scenario: Retry after failure
- **WHEN** an admin retries a `failed` request and processing then succeeds
- **THEN** the request becomes `completed` and the earlier error stays in its history

### Requirement: Audit logging
The activity log SHALL record each of these events, with the request and the acting user: request created, approved, rejected, retried, completed, failed, restored, archive downloaded.

#### Scenario: Approval is logged
- **WHEN** an admin approves a request
- **THEN** an activity log entry records the approval, the admin and the request

### Requirement: Admin permissions
The admin panel SHALL guard these actions with permissions:

| Permission | Allows |
| --- | --- |
| `data-request-list` | list |
| `data-request-view` | detail |
| `data-request-approve` | approve, reject, retry |
| `data-request-restore` | restore |
| `data-request-archive-download` | archive download |

The permissions SHALL be seeded, and the super-admin role SHALL hold all of them.

#### Scenario: View-only admin
- **WHEN** an admin with only `data-request-list` and `data-request-view` opens a pending request
- **THEN** the details and live summary are shown, the approve and reject actions are not, and posting an approval directly is refused with 403
