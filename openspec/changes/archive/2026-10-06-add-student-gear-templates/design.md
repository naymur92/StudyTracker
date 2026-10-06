# Design

## Context

For motivation, see proposal.md → Why.

Today, `WeeklyPlanService::generateBlocks()` reads `config("study.gear_templates.{$gear}")`. Each template has `office`, `off` and `first_off_day_only` lists, and `off_days` from `StudyPreferences` decides the type of each day.

The SPA hard-codes the gear hours in `resources/js/components/weekly/weeklyMeta.js` (≈ 19 / ≈ 11 / ≈ 2 h). Those numbers are only correct for a job holder with two off days.

The text calls day types "office" and "off" in four places:
- `StudyPreferencesForm.vue`
- `WeeklyPlanPage.vue`
- `features.js`
- `userGuide.js` and the `weekly-gear-blocks` entry in `learningScience.js`

Preferences live in `users.study_preferences` (JSON) merged over `config('study.preference_defaults')`, so a new key needs no migration.

## Goals / Non-Goals

**Goals:**
- Student-shaped weeks from the same three gears, without changing anything for existing users.
- One template shape for both profiles, so `generateBlocks()` stays a single code path.
- Server-computed gear hours, so the UI is right for any profile and set of off days.

**Non-Goals:**
- A fourth "exam period" gear. Students in exam season can use Green; a dedicated gear can follow later.
- User-editable templates, or more than two profiles.
- Changing preference defaults per profile (budget, cap). Both profiles keep the current defaults.
- Choosing a profile at registration. It is set in Study Settings.

## Decisions

### D1. Templates keyed by profile, same shape

`gear_templates` becomes `gear_templates.{profile}.{gear}`, with the profiles `job_holder` and `student`. Every gear keeps the keys `workday`, `off` and `first_off_day_only`. The old key `office` is renamed to `workday`, because the key is now shared by office days and class days.

The job-holder values are moved over unchanged. Student values follow proposal.md. The student Yellow template puts Block A on *every* free day, so its `first_off_day_only` list is empty.

`generateBlocks()` resolves the template with `config("study.gear_templates.{$prefs->studyProfile()}.{$gear}")`. The rest of the method is unchanged.

Alternatives considered:
- **Separate gear names per profile** (for example `student_green`). This would leak into the API, the stored `study_weeks.gear` values and the UI.
- **Scaling the job-holder templates by a factor.** Student days differ in *shape* (a class recap, and two long blocks on free days), not just in size.

### D2. Two new slots

Two slots are added:
- `class_recap`: same-day recall of the day's lectures. The first review comes when forgetting is steepest, so the material enters the system early.
- `deep`: a deep block without a time of day. `morning` is a job-holder notion.

They are added to `StudyBlock::SLOTS` and to `config('study.slot_order')`: `morning, class_recap, deep, block_a, block_b, review, minor, other`. `study_blocks.slot` is `varchar(10)` and `class_recap` has 11 characters, so a small migration widens the column to 20. `weeklyMeta.js` gets the labels "Class recap" and "Deep block".

### D3. `gear_options` computed on the server

`WeeklyPlanService::gearOptions(StudyPreferences)` walks a seven-day week. For each day it counts the template minutes for that day type, adding `first_off_day_only` on the first off day. It returns, for each gear, `{gear, label, description, minutes, hours}`, where `hours = round(minutes / 60)`.

Descriptions come from a small per-profile map in config (`gear_descriptions.{profile}.{gear}`):
- Student Yellow: "Assignment or deadline week".
- Job-holder Yellow: "Busy week: release, guests, Ramadan".

The weekly plan payload adds `study_profile` and `gear_options`. `weeklyMeta.js` keeps only the colours, and the page and widget read the hours and descriptions from the payload.

Note that job-holder Yellow becomes "12 h" (705 / 60 = 11.75). The hard-coded "≈ 11 h" was rounded down.

### D4. Profile changes apply forward only

The profile is read at generation time, by `create` and `regenerate`. Existing blocks stay as stored. This matches how schedule snapshots already work for topics, and it keeps marked history honest.

### D5. Preference handling

- `config('study.preference_defaults')` gains `'study_profile' => 'job_holder'`.
- `StudyPreferences::studyProfile()` returns it.
- `UpdateStudyPreferencesRequest` adds `Rule::in(['job_holder', 'student'])`.
- The form gains a two-option selector and switches the off-days label between "Off days (no office)" and "Days without classes".

### D6. Content updates

**Catalog: the `weekly-gear-blocks` entry.**
- Its rule describes both profiles.
- Its example adds the student counts (26 / 19 / 7 blocks).
- A benefit is added for the class recap: an early first review while forgetting is steepest, citing Murre & Dros (2015). That reference is already verified and in the list.

**Other content.**
- The User Guide sections for Weekly Plan and Study Settings explain the profile and the class/free-day wording.
- The weekly-plan feature card in `features.js` says "workdays (office or class days) and off days".

## Risks / Trade-offs

- **Renaming the `office` key breaks other code that reads the config.** → Only `WeeklyPlanService` reads it, as confirmed by grep. The tests cover both profiles.
- **Students who already have plans keep job-holder blocks until they regenerate.** → This is intended (D4). The guide says so.
- **The student template sizes are judgement calls.** → They are config values, easy to tune. The guide frames them as starting points.

## Migration Plan

One migration widens `study_blocks.slot` from 10 to 20 characters. It is additive, and its `down()` narrows the column again, which works only once no `class_recap` blocks remain. Deploy config and code together; deploy.sh already runs `migrate` and `config:cache`. Rollback is reverting the commit; any blocks with the new slots render as "Other" in the old UI.

## Open Questions

- Whether a dedicated exam-period gear for students is wanted. This is deferred, and it does not affect this change.
