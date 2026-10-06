# Spec Delta

## Purpose

Keep a verified, private copy of every row a deletion request removes. Admins can download the copy and, within a retention period, restore it. After that the copy is purged for good.

## ADDED Requirements

### Requirement: Archive contents
Before deleting anything, the system SHALL write one archive per request. The archive SHALL contain:
- a format name and version;
- the request reference, user ID, user email and categories;
- the creation time;
- every row to be deleted, grouped by table, with all stored column values;
- every reference to be cleared (table, row, column, previous value);
- the previous `study_preferences` value when `study_settings` is included.

#### Scenario: Archive mirrors the deletion
- **WHEN** a request for `weekly_plans` is processed for a user with 2 plans and 9 blocks
- **THEN** the archive lists those 2 plans and 9 blocks with all their column values, and the deleted counts match

#### Scenario: Cleared references are recorded
- **WHEN** a request for `topics` is processed and 3 weekly blocks point at those topics
- **THEN** the archive records each block's ID with its previous topic ID

### Requirement: Archive integrity check
The archive SHALL be stored compressed, with its size and SHA-256 checksum recorded on the request. Before any row is deleted, the system SHALL read the stored file back and check both the checksum and the row count for each table. If either does not match, the request SHALL fail and nothing SHALL be deleted.

#### Scenario: Verified archive
- **WHEN** a deletion completes
- **THEN** the request shows the archive's size and checksum, and the stored file's SHA-256 matches the checksum

#### Scenario: Verification mismatch
- **WHEN** the stored archive's row counts do not match the collected rows
- **THEN** the request becomes `failed` and all of the user's data remains

### Requirement: Private archive storage
Archives SHALL be stored on the application's private disk, under a path built from the user ID and a random request identifier. No public URL SHALL serve them.

#### Scenario: No public access
- **WHEN** anyone requests the archive path through the public web server
- **THEN** no archive content is returned

### Requirement: Archive download
An admin with `data-request-archive-download` SHALL be able to download the archive of a request whose archive has not been purged. Each download SHALL be written to the activity log. Users SHALL NOT be able to download archives.

#### Scenario: Download archive
- **WHEN** an admin with the permission selects "Download archive" on a completed request
- **THEN** the compressed archive file is downloaded and the download is logged

#### Scenario: Missing permission
- **WHEN** an admin without `data-request-archive-download` requests the download URL
- **THEN** access is denied with 403

### Requirement: Archive retention and purge
Archives SHALL be kept for `study.data_deletion.archive_retention_days` days after the request completed (default 90). A daily scheduled purge SHALL delete each expired archive file and record the purge time on its request. The request record itself SHALL be kept.

#### Scenario: Expired archive is purged
- **WHEN** the purge runs and a request completed 91 days ago under the default retention
- **THEN** its archive file is deleted, the purge time is recorded, and the request still appears in the admin list

#### Scenario: Archive within retention
- **WHEN** the purge runs and a request completed 10 days ago
- **THEN** its archive is kept

### Requirement: Restore preview
For a `completed` request whose archive has not been purged, an admin with `data-request-restore` SHALL be able to preview a restore without changing data. For each table the preview SHALL show how many rows would be restored and how many skipped, with the reason for each skip, and the references that would be put back.

#### Scenario: Preview with a clash
- **WHEN** an admin previews restoring a `weekly_plans` archive, and the user has since created a plan for one of the archived weeks
- **THEN** the preview shows that week and its blocks as skipped because a plan already exists, and every other row as restorable

### Requirement: Restore from archive
After confirming the preview, an admin with `data-request-restore` SHALL be able to restore the archive in one transaction. Restore SHALL:
- put rows back with their original IDs and values;
- put back the cleared references that still point at existing rows;
- set the request to `restored`, recording who restored it, when, and a per-table report.

A request SHALL be restored at most once.

#### Scenario: Restore keeps old links working
- **WHEN** an archive holding a topic and its tasks is restored without clashes
- **THEN** the topic and tasks exist again with their original IDs, so the topic's earlier hashed URL opens it again

#### Scenario: Restore twice
- **WHEN** an admin tries to restore a request that is already `restored`
- **THEN** the action is refused and nothing changes

### Requirement: Restore skips rows that clash
If a row with the same ID or the same unique key already exists, the restore SHALL skip the archived row, along with every archived row that requires it. Current data always wins. Each skipped row SHALL appear in the report with its reason.

#### Scenario: Review snapshot already exists
- **WHEN** an archived review-load snapshot has the same date as one recorded after the deletion
- **THEN** the archived snapshot is skipped and the current one is kept

#### Scenario: Dependent rows skipped with their parent
- **WHEN** an archived weekly plan is skipped because the user has a plan for that week
- **THEN** its archived blocks are skipped too

### Requirement: Restore repairs slugs and references
If a topic's slug is now taken, the restore SHALL give the topic a free slug and note the change in the report. If a nullable reference points at a row that no longer exists, it SHALL be set to null. If a required reference does, the row SHALL be skipped.

#### Scenario: Slug taken since deletion
- **WHEN** an archived topic has slug `closures` and the user has since created a topic with that slug
- **THEN** the archived topic is restored with a different free slug, and the report notes the change

#### Scenario: Category gone since deletion
- **WHEN** an archived topic's category was deleted after the archive was made
- **THEN** the topic is restored with no category

### Requirement: Restore keeps current preferences
Archived study preferences SHALL be restored only if the user has no saved preferences.

#### Scenario: Current preferences kept
- **WHEN** an archive includes study preferences and the user has saved new preferences since
- **THEN** the user's current preferences stay, and the report notes that the archived ones were skipped

### Requirement: Restore unavailable without an archive
Restore and download SHALL be unavailable for requests whose archive has been purged, or that never produced one (rejected, pending, failed). The detail page SHALL explain why.

#### Scenario: Purged archive
- **WHEN** an admin opens a completed request whose archive was purged
- **THEN** the restore and download actions are not offered and the page shows when the archive was purged
