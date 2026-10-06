# Design

## Context

For motivation, see proposal.md → Why. For behavior, see `specs/data-deletion-requests` and `specs/data-archives`. This document covers how to build it on the current code.

### Current state that shapes the approach

**Data that belongs to a user** (all keyed by `user_id`):

| Table | Notes |
| --- | --- |
| `topics` | Soft deletes. `kind` is `topic` or `mistake`. Self-reference `parent_topic_id`, null on delete. `category_id`, null on delete. Unique (`user_id`, `slug`). |
| `study_tasks` | `topic_id`, cascade. Self-reference `parent_task_id`, null on delete. |
| `practice_logs` | `topic_id`, cascade. `task_id`, null on delete. |
| `categories` | Soft deletes. A null `user_id` marks a system category. |
| `category_review_schedules` | Unique (`user_id`, `category_id`). `category_id`, cascade. A user can hold a schedule on a system category. |
| `topic_revision_templates` | A null `user_id` marks a system template. |
| `study_weeks` | Unique (`user_id`, `week_start`). |
| `study_blocks` | `study_week_id`, cascade. `topic_id` and `category_id`, null on delete. |
| `emailed_study_reports` | No file on disk: the CSV is attached to the email and never stored. |
| `review_load_snapshots` | Unique (`user_id`, `snapshot_date`). Written every night by `study:snapshot-review-load`. |

`users.study_preferences` (JSON) completes the list. Null means defaults; `StudyPreferences` merges saved values over the defaults.

**How data is deleted today.** `ResetDemoUser::wipeUserData()` deletes table by table and relies on database cascades. That is fine for demo data, but it gives neither exact counts nor an archive.

**Admin panel.**
- Blade pages are guarded per action with `$this->authorize('<permission>')`, using Spatie permission names (see `BackupController`).
- Permissions are listed in `PermissionTableSeeder`. `CreateAdminUserSeeder` syncs every permission to the super-admin role.

**Queue and scheduler.**
- Production runs `queue:work --queue=emails,default --tries=3` under Supervisor, with a cron scheduler.
- Tests use the sync queue on in-memory SQLite.

**Activity log.**
- `ActivityLogger::log($description, $subject, $causer, $properties, $event, $logName)` takes an explicit causer.
- The `logActivity()` helper does not, so it cannot be used inside a queue job.

## Goals / Non-Goals

**Goals:**
- The rows that are archived are exactly the rows that are deleted. A row is never deleted unless it is in a verified archive.
- One definition of "what a category covers" drives the user summary, the admin live summary, the archive, the deletion and the restore, so they cannot drift apart.
- A restore is faithful: same IDs, same stored values. Old hashed links keep working.

**Non-Goals:**
- Account deletion, cancellation by the user, email notifications, archive download by the user, and deleting by date range.
- Encrypting archives at rest (see Risks).
- Merging during a restore. A restore never overwrites or merges with current rows; current data always wins.
- Restoring part of an archive (only some tables or rows).

## Decisions

### D1. One `data_deletion_requests` table, with status as a plain string

The table has these columns:
- `id`
- `uuid`: unique, used in the archive path and as the request reference
- `user_id`: nullable, null on delete, so the audit trail survives the user
- `categories`: JSON
- `reason`: text, nullable
- `status`: string, indexed with `user_id`
- `request_counts`: JSON, the per-category counts at request time
- `reviewed_by`, `reviewed_at`, `rejection_reason`
- `processing_started_at`, `completed_at`, `failed_at`, `error_message`
- `archive_path`, `archive_size`, `archive_checksum`, `archive_purged_at`
- `deleted_counts`: JSON
- `restored_by`, `restored_at`, `restore_report`: JSON
- timestamps

Status is a string column with constants on the model, because SQLite enums become CHECK constraints (the same decision as the learning-system change). The model uses `HashesIds`, so the API exposes opaque IDs.

*Alternative considered*: a separate events table for the timeline. Rejected because the fixed set of timestamp and actor columns covers the timeline, and the activity log already holds the full audit.

### D2. A declarative category registry, with a closure over foreign keys

`DataCategoryRegistry` (in `app/Services/StudyTracker/DataDeletion/`) declares three things.

**Root selectors per category.** Each is a table plus a user-scoped query. All selectors include soft-deleted rows.
- `topics` → `topics where kind = topic`
- `mistakes` → `topics where kind = mistake`
- `practice_logs` → `practice_logs`
- `weekly_plans` → `study_weeks`
- `categories` → `categories`
- `study_settings` → `topic_revision_templates` and `category_review_schedules`, plus the `study_preferences` column
- `report_history` → `emailed_study_reports`
- `review_history` → `review_load_snapshots`

**Foreign-key edges**, each marked `required` or `nullable`:

| Child column | Parent | Kind |
| --- | --- | --- |
| `study_tasks.topic_id` | `topics` | required |
| `practice_logs.topic_id` | `topics` | required |
| `practice_logs.task_id` | `study_tasks` | nullable |
| `study_tasks.parent_task_id` | `study_tasks` | nullable |
| `topics.parent_topic_id` | `topics` | nullable |
| `topics.category_id` | `categories` | nullable |
| `study_blocks.study_week_id` | `study_weeks` | required |
| `study_blocks.topic_id` | `topics` | nullable |
| `study_blocks.category_id` | `categories` | nullable |
| `category_review_schedules.category_id` | `categories` | required |

**Delete order**, children first: `practice_logs`, `study_tasks`, `study_blocks`, `study_weeks`, `topics` (mistakes before regular topics, by ID set), `category_review_schedules`, `categories`, `topic_revision_templates`, `emailed_study_reports`, `review_load_snapshots`.

`DataCollector::collect(User, categories, lock: bool)` builds the plan:
1. Run the root selectors and record their IDs.
2. Repeat until nothing changes:
   - For each required edge, add any child rows that point at a collected parent. This mirrors the cascade.
   - For each nullable edge, record a *reference to clear* for every child row that is not itself collected.
3. Check that every collected row has `user_id` equal to the requester. If any row fails the check, throw. This guards "never another user's data" even if an edge is wrong.

The result, a `DeletionPlan`, maps each table to its IDs and rows, and also holds the references to clear and the preferences flag. It deduplicates by itself: picking both `topics` and `practice_logs` counts each log once.

*Alternative considered*: hand-written cascade logic per category. Rejected because it is easy to miss an edge (mistake → parent topic, block → category), and then the summary, archive and deletion drift apart. The registry is also the single place to update when a future change adds a user-owned table. The tasks add a test that fails if a table with a `user_id` foreign key is neither registered nor explicitly excluded.

### D3. Raw rows through the query builder, not Eloquent

Collection, archiving, deletion and restore all use `DB::table()`.

This keeps stored column values exactly as they are: raw JSON strings, dates as stored, and the `deleted_at` of soft-deleted rows. It also skips casts, accessors, `$hidden`, global scopes and model events. Deletes are explicit `whereIn('id', …)->delete()`, so a cascade never removes a row that was not collected. Restoring a raw row is a plain `insert`, and it keeps the ID.

### D4. Archive format and storage

Each archive is one gzip-compressed JSON document on the `local` disk (root `storage/app/private`), at `data-archives/{user_id}/{uuid}.json.gz`:

```json
{
  "format": "studytracker.data-archive", "version": 1,
  "request": "<uuid>", "user": {"id": 7, "email": "…"},
  "categories": ["topics", "categories"], "created_at": "…",
  "tables": {"topics": [{…raw row…}], "study_tasks": […], …},
  "references": [{"table": "study_blocks", "id": 41, "column": "topic_id", "value": 12}],
  "user_columns": {"study_preferences": "<raw json or null>"}
}
```

`DataArchiveService::write()` encodes the document, compresses it with `gzencode`, stores it, and returns the path, size and SHA-256 of the stored bytes. `verify()` reads the file back from disk, checks the checksum, decompresses it, and compares the row count of each table and the reference count with the plan. `read()` is used by restore and preview.

The archive directory and config keys (`study.data_deletion.archive_disk`, `archive_dir`, `archive_retention_days`) live in `config/study.php`. Retention is overridable with `DATA_ARCHIVE_RETENTION_DAYS`.

*Alternatives considered*: a database archive table (the user chose a file), and one file per table (one document is simpler to checksum, download and purge).

### D5. Deletion runs in a queued job, inside one transaction that also writes the archive

Approval sets the status to `approved` with a conditional update (`where status = pending`). The update must affect exactly one row, which blocks double approval. Approval then dispatches `ProcessDataDeletionRequestJob` on `default`, with `$tries = 1`, `$timeout = 300` and `failOnTimeout`. Retrying is always a deliberate admin action. A destructive job is never retried automatically by the worker's `--tries=3`.

The job's handler, `ExecuteDataDeletionService`:
1. Moves the request from `approved` to `processing` with a conditional update. If no row is affected, it exits: another worker, or a duplicate dispatch, got there first.
2. Inside `DB::transaction`:
   - locks the user row (`lockForUpdate`);
   - collects with `lock: true`, which adds `lockForUpdate` to the root selectors;
   - writes the archive and verifies it;
   - clears the references;
   - deletes the IDs table by table in delete order;
   - if `study_settings` is selected, sets `users.study_preferences` to null.
3. After commit, marks the request `completed` with the deleted counts and archive metadata, and writes the activity log.
4. On any throwable:
   - rolls back;
   - deletes the archive file if it was written;
   - marks the request `failed` with `error_message` (trimmed to 2000 characters, as in `ProcessEmailedStudyReportJob`);
   - logs the failure.

Writing the archive inside the transaction is what makes "archived set = deleted set" hold. In InnoDB, locked parent rows block concurrent inserts of children that reference them, such as a new task on a locked topic. New *root* rows created after collection are simply not in the plan, so they are not deleted, as the spec requires. The file write is not transactional, which is why step 4 removes it on rollback.

*Alternative considered*: running the deletion synchronously in the admin request. Rejected because large accounts could hit the web timeout, and the queued job lets the detail page show `processing` and `failed`. Tests run it inline because the queue is sync.

### D6. One summary path for the user and the admin

`DataDeletionSummaryService` calls `DataCollector::collect(…, lock: false)` and turns the plan into:
- counts per table;
- the earliest and latest date per table, from each table's natural date column (`first_study_date`, `scheduled_date`, `practiced_on`, `week_start`, `snapshot_date`, otherwise `created_at`);
- up to 20 sample titles for topics, mistakes and categories;
- references grouped by table and column.

What each view asks for:
- **User summary**: collects each category on its own, giving per-category counts plus the static side-effect notes from the registry.
- **Request snapshot**: the per-category counts at the moment the request is created.
- **Admin live summary**: collects the request's categories together and compares the totals with the snapshot.

Because the summary and the deletion share the collector, the numbers an admin approves are the numbers that get deleted, apart from rows added in between.

### D7. Restore is synchronous, with a preview that runs the same plan

`RestoreDataDeletionService` has two methods: `plan(request)` (the preview) and `restore(request, admin)`. Both read the archive and walk the tables in reverse delete order, parents first.

Rules applied to each row:
- **Same ID exists** → skip (`id_exists`).
- **A declared unique key clashes**:
  - `study_weeks (user_id, week_start)`, `review_load_snapshots (user_id, snapshot_date)` and `category_review_schedules (user_id, category_id)` → skip (`unique_exists`);
  - `topics (user_id, slug)` → re-slug to `<slug>-restored`, then `-restored-2` and so on, and report the change.
- **Required parent is skipped or missing** → skip (`parent_missing`). Skips spread to dependants this way.
- **Nullable reference to a row that does not exist and is not being restored** → set to null and report it.

After the rows, each archived reference is put back with `update … set column = old value`, but only when three things hold: the row exists, the column is still null, and the target exists. If the user has since set it themselves, their value is kept.

`study_preferences` is restored only if it is currently null.

`restore()` runs in one transaction. It re-checks `status = completed` and `archive_purged_at is null` under a lock on the request row, then inserts, sets `restored`, and stores the report from the preview recomputed at that moment.

Restore runs synchronously in the admin request. Restores are rare, the admin wants the report straight away, and per-user data is small: thousands of rows at most. The preview is a GET that shows the same report without writing.

Restored tasks keep their stored status and dates. Overdue pending tasks become `missed` at the next `study:mark-missed` run, and no re-planning is triggered. This keeps restore a faithful copy rather than a re-computation.

### D8. Admin panel

`App\Http\Controllers\DataDeletionRequestController` lives under `/admin/data-requests`, named `data-requests.*`:

| Route | Action | Permission |
| --- | --- | --- |
| GET `/` | index | `data-request-list` |
| GET `/{dataDeletionRequest}` | show | `data-request-view` |
| POST `/{…}/approve` | approve | `data-request-approve` |
| POST `/{…}/reject` | reject (validates `rejection_reason`) | `data-request-approve` |
| POST `/{…}/retry` | retry (`failed` → `approved`, re-dispatch) | `data-request-approve` |
| GET `/{…}/restore` | restore preview page | `data-request-restore` |
| POST `/{…}/restore` | restore | `data-request-restore` |
| GET `/{…}/archive` | archive download (streamed from the private disk) | `data-request-archive-download` |

Each action calls `$this->authorize()`, following the `BackupController` pattern. Review logic lives in `ReviewDataDeletionRequestService`, with approve, reject and retry and their conditional status updates. The controller only maps results to redirects and flash messages.

The views are Bootstrap 4 / SB Admin 2 pages under `resources/views/pages/data-deletion-requests/`, as in `activity-logs`:
- `index`: filters and a table;
- `show`: details, timeline, request-time counts and live summary side by side, an approve confirmation modal, a reject modal with a reason, and the archive panel;
- `restore`: the preview report and a confirm button.

The live summary is computed only while the request is open. For a closed request, the page shows the stored counts instead.

Dashboard and sidebar:
- `DashboardController@index` adds `pendingDataRequests` (the count and the five oldest) when the user can `data-request-list`.
- The sidebar badge count comes from a view composer for `layouts.sidebar`, registered in `AppServiceProvider`. It runs one `count()` query, and only for users holding the permission.

### D9. User API and SPA

API routes, inside the `auth:api` → `study` group:

| Route | Limiter | Notes |
| --- | --- | --- |
| GET `/data-deletion/summary` | `study-read` | |
| GET `/data-deletion/requests` | `study-read` | |
| POST `/data-deletion/requests` | `study-write` | also `deny.demo` |
| GET `/data-deletion/requests/{dataDeletionRequest}` | `study-read` | |

Supporting classes:
- **Controller**: `Api/StudyTracker/DataDeletionApiController`. On show it checks ownership and responds 404 rather than 403, so it does not reveal that the request exists.
- **Form Request**: `StoreDataDeletionRequest`:
  - `categories`: required, array, min 1, distinct, `in:` the registry keys
  - `reason`: nullable, ≤1000 characters
  - `confirmation`: required, `in:DELETE`
- **Service**: `CreateDataDeletionRequestService`. It enforces the one-open-request rule with a `lockForUpdate` on the user row inside a transaction, which closes the double-submit race, and throws a `ValidationException` on `categories`.
- **Resource**: `DataDeletionRequestResource`. It emits the hashed ID, a status label and the counts, and never the archive path or checksum.

In the SPA:
- `stores/dataDeletion.js` follows the existing stores.
- `components/profile/DataDeletionSection.vue` is mounted on `ProfilePage.vue`.
- `components/profile/DataDeletionConfirmModal.vue` follows the `CategoryScheduleModal.vue` modal pattern. Its confirm button stays disabled until the input equals `DELETE`.
- Demo users get the disabled state through the auth store's `isDemoUser` getter.

### D10. Retention purge

The command is `data-requests:purge-archives`, at `app/Console/Commands/PurgeDataArchives.php`, scheduled daily at 00:10. It selects requests whose `archive_path` is set and `archive_purged_at` is null, and whose `completed_at` is older than the retention period. Retention counts from completion, even if the request was later restored. For each, it deletes the file (an already-missing file is fine), sets `archive_purged_at`, and logs a summary line. A `--dry-run` option lists what would be purged.

### D11. Activity log

Events use `ActivityLogger::log()` with log name `data_deletion`, the request as subject, and an explicit causer:
- the user, for `created`;
- the admin, for `approved`, `rejected`, `retried`, `restored` and `archive_downloaded`;
- the approving admin, for `completed` and `failed`, since the job has no auth context.

Properties carry the categories and counts. They never carry row contents.

## Risks / Trade-offs

- **[Archives hold personal data in plain gzip]** → They live on the private disk, outside the web root, under a random UUID path. Download needs a dedicated permission and is logged, and archives are purged after the retention period. Encrypting with `Crypt` would tie archives to `APP_KEY`, so rotating the key would make them unreadable. It is deferred and can be added later by bumping the archive `version`.
- **[Memory on very large accounts]**: the whole plan and document are held in memory. → Per-user data here is small (hundreds to a few thousand rows). The job has a 300-second timeout, and failure is safe because nothing is deleted. If needed, the archive can later be streamed in chunks without changing the format.
- **[A new user-owned table is added later and missed by the registry]** → A test scans the schema for `user_id` columns and fails unless each table is registered or listed in an explicit exclusion list: `activity_logs`, `login_history`, `oauth_*`, `sessions`, `files`, `forgot_password_codes`, `email_verification_tokens`, `data_deletion_requests`.
- **[Restore after the schema changes]** → The archive records `version`. Restore only inserts columns that still exist in the current table: missing columns are dropped, and new ones take their defaults. Dropped columns are noted in the report.
- **[Locks on SQLite in tests]** → `lockForUpdate` does nothing on SQLite. Concurrency safety rests on the conditional status updates, which are tested, while row-level locking only matters in production MySQL.
- **[The queue worker is down when an admin approves]** → The request stays `approved`, and the detail page says it is waiting for the worker. Nothing is deleted until the job runs.
- **[An admin approves before reading the live summary]** → The approve modal repeats the live totals and the categories, and needs an explicit confirmation.

## Migration Plan

1. Deploy the migration (one new table; nothing existing changes), the code and the config. `php artisan migrate` runs in `deploy/deploy.sh`.
2. Run `php artisan db:seed --class=PermissionTableSeeder --force`. The seeder also grants every listed permission to an existing `Super Admin` role (`CreateAdminUserSeeder` cannot be re-run, because it creates the admin user). Other roles get the permissions through the Roles page. This step is in the README.
3. The cron scheduler picks up the purge command. The Supervisor worker already listens on `default`, and `queue:restart` runs during deploy.
4. **Rollback**: roll back the migration and the code. No existing data is touched by the deploy. Archives already written stay in `storage/app/private/data-archives/` and can be removed by hand.
