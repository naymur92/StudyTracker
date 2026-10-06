# Tasks

Groups are ordered by dependency, and each group lands its own tests and docs:

| Groups | Covers |
| --- | --- |
| 1 | foundation |
| 2–3 | the shared collection engine |
| 4 | the user API |
| 5–6 | archive and deletion |
| 7 | admin review |
| 8 | restore |
| 9 | download and purge |
| 10 | the SPA |
| 11 | integration checks |

Testing conventions:
- API feature tests extend `Tests\Feature\Study\StudyApiTestCase` and use `getJson`/`postJson`, because of the `api.headers` middleware.
- Admin feature tests use session auth (`actingAs($admin, 'web')`) with an admin of `type` 1, `is_active` 1, and permissions from `PermissionTableSeeder`.
- Archive tests use `Storage::fake('local')`.

## 1. Foundation: config, schema, model, permissions

- [x] 1.1 Add `data_deletion` to `config/study.php`:
  - `archive_disk` (`local`)
  - `archive_dir` (`data-archives`)
  - `archive_retention_days` (env `DATA_ARCHIVE_RETENTION_DAYS`, default 90)

  Add `DATA_ARCHIVE_RETENTION_DAYS` to `.env.example`. Verify that `php artisan config:show study.data_deletion` prints the three keys.
- [x] 1.2 Create the `data_deletion_requests` migration with the columns in design D1:
  - `uuid` unique
  - `user_id` nullable, null on delete
  - `reviewed_by` and `restored_by`, nullable, null on delete, referencing `users`
  - an index on (`user_id`, `status`)

  Verify the round trip: `php artisan migrate`, then `migrate:rollback --step=1`, then `migrate`.
- [x] 1.3 Create `App\Models\DataDeletionRequest`:
  - `HashesIds`
  - status constants and `OPEN_STATUSES` (`pending`, `approved`, `processing`, `failed`)
  - JSON casts for `categories`, `request_counts`, `deleted_counts`, `restore_report`
  - datetime casts
  - relations `user`, `reviewer`, `restorer`
  - scopes `open()` and `status()`
  - helpers `isOpen()` and `hasArchive()` (a path is set and it has not been purged)
  - `uuid` filled on create

  Add `User::dataDeletionRequests()`. Verify with a unit test that creating a request fills `uuid` and that `hasArchive()` is false after `archive_purged_at` is set.
- [x] 1.4 Add the five `data-request-*` permissions to `PermissionTableSeeder`. Verify that running `PermissionTableSeeder` then `CreateAdminUserSeeder` on a fresh database gives the super-admin role all five (assert this in a test).

## 2. Category registry and collector

- [x] 2.1 Implement `App\Services\StudyTracker\DataDeletion\DataCategoryRegistry` as in design D2:
  - the eight category keys, with labels, descriptions and side-effect notes
  - root selectors that include soft-deleted rows
  - the foreign-key edges (required or nullable)
  - the delete order
  - the date column and title column per table

  Verify with a unit test that `keys()` returns exactly the eight keys and every edge's tables appear in the delete order.
- [x] 2.2 Implement `DataCollector::collect(User $user, array $categories, bool $lock = false): DeletionPlan`. It runs the root selection, then the fixpoint closure (required edges add children; nullable edges record references), then the same-user check. `DeletionPlan` exposes:
  - `tables()`: table → raw rows, keyed by ID
  - `references()`
  - `clearsPreferences()`
  - `counts()`

  Verify with `tests/Feature/Study/DataDeletion/DataCollectorTest.php`:
  - `topics` collects the topics with their tasks and logs; mistakes and blocks become references
  - `mistakes` collects only `kind = mistake`
  - `categories` collects soft-deleted categories and their schedules, and turns topic and block `category_id` into references
  - system categories and templates and other users' rows are never collected
  - picking `topics` and `practice_logs` together counts each log once
  - a row owned by another user that is reached through an edge throws
- [x] 2.3 Add a schema coverage test that lists every table with a `user_id` column (through `Schema::getColumnListing`) and fails unless the table is registered or listed in the explicit exclusion list from design (Risks). Verify that the test passes now and fails if a registered table is removed from the registry.

## 3. Data summary

- [x] 3.1 Implement `DataDeletionSummaryService` (design D6). It provides:
  - `forUser(User)`: per-category counts and per-table breakdown, plus notes
  - `forCategories(User, categories)`: counts per table, earliest and latest dates, up to 20 sample titles for topics, mistakes and categories, and references grouped by table and column
  - `diff(snapshot, live)`

  Verify with a feature test that seeds a user with known data and asserts the counts, dates, the 20-item title cap and the diff values.

## 4. User API: summary and requests

- [x] 4.1 Create `StoreDataDeletionRequest` with the rules in design D9, including category keys validated against the registry. Verify with tests: an unknown key gives 422 on `categories`, a duplicate key gives 422, an empty array gives 422, and a wrong `confirmation` gives 422 on `confirmation`.
- [x] 4.2 Implement `CreateDataDeletionRequestService`:
  - a transaction with `lockForUpdate` on the user row
  - the one-open-request check, throwing a `ValidationException` on `categories`
  - the `request_counts` snapshot
  - an activity log `created` entry with the user as causer

  Verify with tests: a second request while pending gives 422; a new request after a rejection gives 201; the activity log entry exists.
- [x] 4.3 Create `DataDeletionRequestResource`, with the hashed ID, categories with labels, reason, status and label, rejection reason, `request_counts`, `deleted_counts`, timestamps, and never the archive path or checksum. Create `DataDeletionApiController` with `summary`, `index`, `store` and `show` (404 for another user's request). Register the four routes in `routes/api.php` with the limiters from D9, and `deny.demo` on `store`.

  Verify with `tests/Feature/Study/DataDeletion/DataDeletionApiTest.php`:
  - the summary counts
  - create gives 201 with the `pending` status and counts
  - the list is newest first
  - show gives 404 for another user's request
  - the demo user gets 403 on create and 200 on summary
  - the response never contains `archive_path`
- [x] 4.4 Document the four endpoints, with request and response examples, in `API-DOCUMENTATION.md` and the `README.md` endpoint list, and add them to `StudyTracker-API.postman_collection.json`. Verify that the documented paths match `php artisan route:list --path=api/study/data-deletion`.

## 5. Archive service

- [x] 5.1 Implement `DataArchiveService` (design D4):
  - `write(DataDeletionRequest, DeletionPlan, User)` returns the path, size and SHA-256. It writes the version-1 document with gzip to `data-archives/{user_id}/{uuid}.json.gz` on the configured disk.
  - `verify(path, checksum, DeletionPlan)` checks the checksum, the row count per table and the reference count, and throws on any mismatch.
  - `read(path)` returns the decoded document.
  - `delete(path)`

  Verify with unit tests: a round trip keeps raw values (JSON strings, `deleted_at`); a tampered byte makes `verify` fail; a row-count mismatch makes `verify` fail.

## 6. Carrying out a deletion

- [x] 6.1 Implement `ExecuteDataDeletionService::run(DataDeletionRequest)` as in design D5:
  - a conditional `approved` → `processing` update, exiting if no row is affected
  - in a transaction: lock the user, collect with locks, write and verify the archive, clear references, delete by ID in delete order, and null `study_preferences` for `study_settings`
  - after commit: mark `completed` with `deleted_counts` and archive metadata
  - on failure: roll back, delete the file, mark `failed` with the trimmed `error_message`
  - activity log `completed`/`failed` entries with the reviewer as causer
- [x] 6.2 Create `ProcessDataDeletionRequestJob` on the `default` queue, with `$tries = 1`, `$timeout = 300` and `failOnTimeout`, calling the service.

  Verify with `tests/Feature/Study/DataDeletion/ExecuteDeletionTest.php`, one case per category, asserting the rows are gone, the deleted counts and the archive contents. Also cover:
  - references are nulled and the referencing rows remain (blocks, mistakes, uncategorised topics)
  - rows created after collection survive
  - `study_settings` resets preferences to defaults through `StudyPreferences`
  - a forced archive failure (a disk mock that throws) leaves all data in place, sets `failed` and leaves no file
  - running the job twice for the same request deletes once

## 7. Admin review

- [x] 7.1 Implement `ReviewDataDeletionRequestService`:
  - `approve(request, admin)`: a conditional `pending` → `approved` update, then dispatch the job
  - `reject(request, admin, reason)`: from `pending` or `failed`
  - `retry(request, admin)`: `failed` → `approved`, keeping the previous `error_message` in the activity log, then re-dispatch

  Each writes its activity log entry. Verify with tests: approving a non-pending request is refused and changes nothing; rejecting deletes nothing; retrying a failed request then succeeds and ends `completed`.
- [x] 7.2 Create `DataDeletionRequestController` with `index` (status filter and name/email search, paginated) and `show`, plus the `approve`, `reject` (validates `rejection_reason`, required, ≤1000 characters) and `retry` actions. Register the routes in `routes/web.php` under `/admin/data-requests` (`data-requests.*`), with `$this->authorize()` per design D8. Verify with `tests/Feature/Admin/DataDeletionRequestAdminTest.php`:
  - 403 without each permission
  - the status filter works
  - approve leads to `completed` (sync queue)
  - reject without a reason gives a validation error
  - a view-only admin cannot post an approval
- [x] 7.3 Build `resources/views/pages/data-deletion-requests/index.blade.php` and `show.blade.php` (SB Admin 2). The show page has:
  - user details, linking to `study-tracker.user-report` when the user is type 3
  - categories, reason and status timeline
  - the request-time counts next to the live summary, with differences highlighted (open requests only)
  - samples and references
  - an approve modal repeating the live totals
  - a reject modal with a reason
  - a retry button for `failed`
  - an archive panel (size, checksum, purge state), shown when `hasArchive()` is true or the archive has been purged

  Verify in the feature test that the show page contains the live counts and the highlighted difference after a topic is added post-request, and that buttons are hidden without `data-request-approve`.
- [x] 7.4 Add pending data requests to the admin dashboard (`DashboardController` plus a card and a list of the five oldest in `dashboard.blade.php`), and a "Data Requests" sidebar item with a pending badge from a `layouts.sidebar` view composer, both gated on `data-request-list`. Verify with a feature test that the dashboard shows the count for a permitted admin and hides it otherwise.
- [x] 7.5 Document the admin workflow and permissions in `README.md` (the admin panel section), and add the permission re-seed step to the deploy notes. Verify that the README lists all five permissions and the `/admin/data-requests` page.

## 8. Restore from archive

- [x] 8.1 Implement `RestoreDataDeletionService` with `plan(request)` and `restore(request, admin)` as in design D7:
  - insert parents first, keeping IDs
  - skip on an existing ID or unique key, spreading skips to dependants
  - re-slug topics
  - null nullable references to missing rows
  - put references back only into null columns pointing at existing targets
  - restore preferences only if currently null
  - drop columns that no longer exist and report them
  - one transaction, with a lock and a re-check of `completed` and not purged
  - status `restored` with `restored_by`, `restored_at` and `restore_report`, plus an activity log entry

  Verify with `tests/Feature/Study/DataDeletion/RestoreTest.php`:
  - a clean restore brings back the same IDs, and the old hashed topic ID resolves through `GET /api/study/topics/{id}`
  - a slug clash leads to a re-slug
  - a week clash skips the week and its blocks
  - a snapshot clash is skipped
  - current preferences are kept
  - a second restore is refused
  - a purged archive is refused
  - the preview writes nothing
- [x] 8.2 Add the `GET` and `POST /admin/data-requests/{id}/restore` routes and a `restore.blade.php` preview page (per-table restorable and skipped counts with reasons, a confirm button), gated on `data-request-restore`. Show a "Restore" action on completed requests that have an archive. Verify with admin feature tests: the preview page lists the skipped rows; the POST restores; 403 without the permission; the action is hidden once purged.

## 9. Archive download and retention purge

- [x] 9.1 Add `GET /admin/data-requests/{id}/archive`, which streams the file from the private disk as `application/gzip`, gated on `data-request-archive-download`, with a `archive_downloaded` activity log entry. It returns 404 when there is no archive. Verify with admin feature tests: download succeeds; 403 without the permission; 404 after purge; the log entry exists.
- [x] 9.2 Create the `data-requests:purge-archives` command with `--dry-run`, as in design D10, and schedule it daily at 00:10 in `routes/console.php`. Verify with a feature test using `travelTo`: a request completed 91 days ago is purged and keeps its record; one completed 10 days ago is kept; `--dry-run` deletes nothing. Also check that `php artisan schedule:list` shows the entry.
- [x] 9.3 Add `data-requests:purge-archives` to the scheduled commands line in `CLAUDE.md` and to `README.md`. Verify that both mention the 00:10 schedule.

## 10. SPA: Profile "Delete my data" section

- [x] 10.1 Create `resources/js/stores/dataDeletion.js`, with `fetchSummary`, `fetchRequests`, `createRequest` and an `openRequest` getter. It follows the existing store and API-client patterns and handles 422 and 403 errors. Verify that `npm run build` succeeds.
- [x] 10.2 Create `components/profile/DataDeletionConfirmModal.vue` (following the `CategoryScheduleModal.vue` pattern). It lists the chosen categories with counts and side-effect notes and the "an admin will review" notice, and its confirm button stays disabled until the input equals `DELETE`. Create `components/profile/DataDeletionSection.vue`, with:
  - category checkboxes showing counts
  - an optional reason
  - a "Request deletion" button that opens the modal
  - an open-request status banner that replaces the form
  - the request history (status, rejection reason, completion date, deleted counts)
  - a disabled state for the demo user (`isDemoUser`)

  Mount it on `ProfilePage.vue`. Verify by hand with `composer dev`:
  - submit `practice_logs`
  - cancelling the modal sends nothing
  - the confirm button is enabled only after typing `DELETE`
  - the request then appears as pending
  - a second submission is blocked
  - the demo login sees the form disabled
- [x] 10.3 Update the `profile` section in `resources/js/content/userGuide.js` with the purpose, steps and tips for "Delete my data": the admin review, the archive and retention, and that the account is not deleted. Verify that `npm run check:content` and `npm run build` pass.

## 11. Integration checks

- [x] 11.1 Run the whole flow end to end in the browser against MySQL (`composer dev`):
  1. A user requests `topics` and `categories`.
  2. An admin sees the request on the dashboard, opens it, checks the live summary and approves it.
  3. The worker completes the deletion, and the topics are gone in the SPA.
  4. The admin downloads the archive. Check it with `gunzip -t`, and check its SHA-256 against the detail page.
  5. The admin previews a restore and restores. The topics are back, and their old URLs open.
- [x] 11.2 Run `composer test` and `vendor/bin/pint --test` on the changed files. Verify that both pass with no failures.
- [x] 11.3 Run `openspec validate add-data-deletion-requests --strict` and verify that it reports the change as valid.
