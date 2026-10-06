# Proposal

## Why

Users can't clear their study data in bulk. They can delete topics, logs or categories one at a time, and only from the screens that show them. Weekly plans, settings, report history and review-load history can't be cleared by the user at all. Admins have no record of who asked for what to be removed. We need a controlled, auditable way to remove a user's data: the user asks, an admin checks the request and approves it, and the data is archived before it is deleted, so a mistake can still be undone.

## What Changes

- **User panel (Vue SPA, Profile page)**: a new "Delete my data" section.
  - The user ticks one or more data categories. Each category shows how many items it holds now.
  - The user can add an optional reason.
  - Before anything is sent, a confirmation prompt says exactly what will be removed and what side effects follow (for example, deleting categories leaves topics uncategorised). The user must type `DELETE` to confirm.
  - The section lists the user's past requests, with their status and any rejection reason.
- **Data categories**:
  - `topics`: topics, with their tasks and practice logs
  - `mistakes`
  - `practice_logs`
  - `weekly_plans`
  - `categories`: own categories, with their review schedules
  - `study_settings`: revision templates, category schedules and study preferences
  - `report_history`: emailed reports
  - `review_history`: review-load snapshots

  System categories, the account itself, login history and activity logs are never included.
- **Request lifecycle**: `pending` → `approved` → `processing` → `completed`, or `failed` (an admin can retry). A pending or failed request can also go to `rejected`, with a required reason. A `completed` request can go to `restored`. Each user can have only one open request (`pending`, `approved`, `processing` or `failed`) at a time. Demo users can't create requests.
- **New API endpoints** under `/api/study/data-deletion`:
  - the user's current data summary per category
  - list, create and show the user's own requests

  They follow the usual conventions: hashed IDs, `jsonResponse`, the `study-read` and `study-write` limiters, and `deny.demo` on create.
- **Admin panel**:
  - The dashboard gets a "Pending data requests" card and a list of the latest pending requests.
  - A new "Data Requests" sidebar menu shows a pending-count badge and leads to a list page with status and user filters.
  - Each request has a detail page. It shows the user, the chosen categories, the reason, and the per-category counts taken when the user asked. It also shows a **live data summary**: row counts per table, date ranges and a sample of titles, with differences from the request-time counts marked.
  - From the detail page an admin can approve (with confirmation) or reject (with a reason). After completion the page shows the archive details, the deleted counts and a download link.
- **Archive, then delete**: approval queues a job.
  - The job collects every affected row (including soft-deleted topics and categories) and every foreign-key reference that would be nulled.
  - It writes a gzipped JSON archive to the private disk, records its SHA-256 checksum and size, and reads the file back to check it.
  - Only after that check passes does it hard-delete exactly the archived rows, in a single transaction.
  - If any step fails, nothing is deleted.
- **Restore from archive**: on a completed request whose archive still exists, an admin can preview a restore (what would be restored, what would be skipped and why) and then run it.
  - Rows go back with their original IDs, so hashed links keep working.
  - When a row clashes with data the user has created since, the user's current data wins, and the clash is listed in a restore report.
- **Retention**: archives are kept for a configurable period (`study.data_deletion.archive_retention_days`, default 90). A new daily command, `data-requests:purge-archives`, deletes expired archives. After that, a restore is no longer possible.
- **Permissions**: new Spatie permissions:
  - `data-request-list`
  - `data-request-view`
  - `data-request-approve` (covers approve, reject and retry)
  - `data-request-restore`
  - `data-request-archive-download`

  They are seeded and given to the super-admin role.
- Every create, approve, reject, complete, fail, restore and download is written to the activity log.

## Capabilities

### New Capabilities
- `data-deletion-requests`: how a user asks for data to be deleted, the data categories and what each one covers, the request lifecycle, the admin review screens (detail page, live data summary, approve and reject), and running an approved deletion.
- `data-archives`: archiving a user's data before it is deleted (format, integrity check, storage, download), the retention period and purge, and restoring from an archive, including how clashes are handled.

### Modified Capabilities
<!-- None: existing specs (study-topics, mistake-notebook, weekly-planning, study-preferences, user-guide…) keep their requirements. The Profile guide section gains content, but no user-guide requirement changes. -->

## Impact

- **Database**: a new `data_deletion_requests` table. No changes to existing tables.
- **Backend**:
  - a new model with `HashesIds`
  - services under `app/Services/StudyTracker/DataDeletion/`: category registry, summary, request, review, archive, execute and restore
  - a queued job, `ProcessDataDeletionRequestJob`, on the `default` queue with `tries = 1`
  - an API controller, Form Request and Resource
  - an admin Blade controller and views
  - an Artisan command and a schedule entry in `routes/console.php`
  - config keys in `config/study.php`
  - new permissions in `PermissionTableSeeder`
- **Frontend**:
  - a new Pinia store, `dataDeletion`
  - a Profile page section with a confirmation modal
  - an updated `profile` section in `resources/js/content/userGuide.js`
- **Admin UI**: `dashboard.blade.php`, `layouts/sidebar.blade.php`, a new `pages/data-deletion-requests/` folder, `DashboardController`.
- **Storage**: a new `storage/app/private/data-archives/` folder. Archives contain personal data. They are never web-accessible, and only admins with the download permission can get them.
- **Docs**: `README.md`, `API-DOCUMENTATION.md`, the Postman collection, and CLAUDE.md (the list of scheduled commands).
- **Ops**: production already runs a queue worker for `emails,default`. The cron scheduler picks up the new purge command.
