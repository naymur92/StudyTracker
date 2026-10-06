# Proposal

## Why

The weekly gear templates in `config/study.php` assume a full-time job:
- an "office" day gets one 90-minute morning block before work;
- an "off" day gets the long blocks.

A student's week is shaped differently. Class days have lectures to consolidate the same day, plus more free hours than a job holder's evening. Days without classes can hold two long deep blocks. Students using StudyTracker today get blocks sized for an office worker, labelled "office days".

## What Changes

- **New study profile preference.** `study_profile` is either `job_holder` (the default, so existing users are unchanged) or `student`. It is set in Study Settings.
- **Student gear templates**, alongside the existing job-holder templates. Each template's blocks depend on the type of day:
  - a job holder's day is an *office day* or an *off day*;
  - a student's day is a *class day* or a *free day*.

  `off_days` decides the day type for both profiles; for a student it means the days without classes.

  | Gear | Student class day | Student free day | Approx. week |
  |---|---|---|---|
  | Green | class recap 30 + deep block 90 + reviews 25 + minor 30 | Block A 150 + Block B 120 + reviews 25 | ≈ 24 h |
  | Yellow (assignment or deadline week) | class recap 20 + deep block 60 + reviews 20 | Block A 120 + reviews 20 | ≈ 13 h |
  | Red (illness, travel, Eid) | reviews 20 | reviews 20 | ≈ 2 h |

  These figures assume five class days and two free days.
- **Two new block slots:**
  - `class_recap`: same-day recall of today's lectures;
  - `deep`: a deep block not tied to the morning.
- **Profile-aware gear options in the weekly plan payload.** The payload gains `gear_options`: each gear's label, description and estimated weekly hours, computed from the user's profile and off days. They replace the hours that are hard-coded in the SPA.
- **Profile-aware wording.** The Weekly Plan page, the preferences form, the User Guide and the "weekly gear blocks" catalog entry say "class days / free days" for students and "office days / off days" for job holders.
- **Scope of a profile change.** Changing the profile affects only blocks generated afterwards (new plans and regenerated blocks). Existing blocks are never rewritten.
- No **BREAKING** changes. The default profile reproduces today's blocks exactly.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `study-preferences`: adds the `study_profile` preference, with its default, validation and the settings UI.
- `weekly-planning`: gear templates are selected by study profile; adds the student templates, the `class_recap` and `deep` slots, and `gear_options` in the weekly plan response.

Both capabilities are introduced by the unarchived change `adaptive-retrieval-learning-system`. Archive that change before this one, so these deltas apply to the main specs it creates.

## Impact

- **Config.** `config/study.php`: `gear_templates` is keyed by profile (`job_holder`, `student`); `slot_order` gains the new slots; `preference_defaults` gains `study_profile`.
- **Backend.**
  - `StudyPreferences` gains a `studyProfile()` getter.
  - `UpdateStudyPreferencesRequest` validates the new preference.
  - `WeeklyPlanService` picks templates by profile and computes gear options.
  - `StudyBlock::SLOTS` gains the new slots.
  - The `WeeklyPlanApiController` payload gains `gear_options`.
- **Frontend.**
  - The Study Settings preferences form gains a profile selector and day-type wording.
  - `weeklyMeta.js` gains slot labels and loses the hard-coded hours.
  - The Weekly Plan page and `ThisWeekWidget` use `gear_options`.
- **Content.** The catalog entry `weekly-gear-blocks`, the User Guide sections (Weekly Plan, Study Settings) and `features.js`.
- **Docs.** `API-DOCUMENTATION.md`, the Postman collection and the README feature bullet.
- **Tests.** Student template block counts, profile validation, `gear_options`, and a regression check that job-holder blocks are unchanged.
