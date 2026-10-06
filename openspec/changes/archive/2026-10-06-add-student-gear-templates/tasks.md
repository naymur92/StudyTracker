# Tasks

Prerequisite: archive `adaptive-retrieval-learning-system` before archiving this change. This change's MODIFIED deltas target the specs it creates.

## 1. Config and preferences

- [x] 1.1 Restructure `config/study.php` (design D1–D3):
  - `gear_templates` becomes `{job_holder, student}.{green, yellow, red}`, each with `workday`, `off` and `first_off_day_only`. The job-holder values move over unchanged; `office` is renamed `workday`.
  - Add the student templates from proposal.md.
  - Add `gear_descriptions.{profile}.{gear}`.
  - `slot_order` gains `class_recap` and `deep`.
  - `preference_defaults` gains `study_profile` = `job_holder`.

  Verify that `php artisan config:show study` shows both profiles.
- [x] 1.2 Add `StudyPreferences::studyProfile()` and validate `study_profile` (`job_holder` or `student`) in `UpdateStudyPreferencesRequest`. Verify by extending `StudyPreferencesTest` and `StudyPreferencesApiTest`: the default is `job_holder`, switching to `student` saves, and `teacher` gets a 422.
- [x] 1.3 Add a "Job holder / Student" selector to `StudyPreferencesForm.vue`. The off-days label switches between "Off days (no office)" and "Days without classes". Verify by hand that saving Student persists after a reload, and that `npm run build` passes.

## 2. Weekly planning

- [x] 2.1 Add `class_recap` and `deep` to `StudyBlock::SLOTS`, and add a migration widening `study_blocks.slot` to 20 characters, since `class_recap` exceeds the current 10. Verify that the migration round-trips, and a feature test that adding a `class_recap` block returns 201 and orders it before `deep` on the same day.
- [x] 2.2 Make `WeeklyPlanService::generateBlocks()` read `gear_templates.{profile}.{gear}` with the `workday` key. Verify with tests:
  - the existing job-holder tests still pass unchanged (21 / 13 / 7 blocks, custom off days);
  - a student gets 26 blocks and 1465 minutes (green), 19 blocks and 780 minutes (yellow, block A on both free days), and 7 blocks (red);
  - student free days follow `off_days`.
- [x] 2.3 Add `WeeklyPlanService::gearOptions()` and include `study_profile` and `gear_options` in the weekly plan payload. Verify feature tests for the student options (1465/24, 780/13, 140/2) and the job-holder options (1130/19, 705/12, 140/2), including a week without a plan.
- [x] 2.4 Verify with a feature test that a profile change keeps existing blocks: a job holder's green week switched to student and regenerated as yellow on Tuesday keeps the earlier and marked blocks, and gets student Yellow blocks from Tuesday on.
- [x] 2.5 Frontend:
  - `weeklyMeta.js` drops the hard-coded hours and adds labels for the new slots;
  - `WeeklyPlanPage.vue` and `ThisWeekWidget.vue` read hours and descriptions from `gear_options`;
  - day wording follows `study_profile` (class/free days or office/off days).

  Verify by hand as a student and as a job holder, and that `npm run build` passes.

## 3. Content, docs and demo

- [x] 3.1 Update the `weekly-gear-blocks` catalog entry (both profiles, student example, the class-recap benefit citing `murre2015`), the Weekly Plan and Study Settings guide sections, and the weekly-plan feature card in `features.js`. Verify that `npm run check:content` passes.
- [x] 3.2 Document `study_profile`, the student templates, the new slots and `gear_options` in `API-DOCUMENTATION.md`, the README and the Postman examples. Verify that the Postman JSON parses.
- [x] 3.3 Verify that `demo:reset` still produces a 21-block green job-holder week, since the demo user keeps the default profile.

## 4. Integration

- [x] 4.1 Run `composer test` (against MySQL locally, since this machine has no pdo_sqlite), `vendor/bin/pint --test` on changed files, and `npm run build`. Verify that all pass.
- [x] 4.2 Run `openspec validate add-student-gear-templates --strict`. Verify that it reports valid.

## Workflow follow-up

- Archive `adaptive-retrieval-learning-system` first, then this change.
