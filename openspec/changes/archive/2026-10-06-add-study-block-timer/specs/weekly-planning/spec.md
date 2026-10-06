# Spec Delta

## ADDED Requirements

### Requirement: Block break pattern
A block SHALL accept an optional break pattern:
- `break_every_minutes`: 10–120;
- `break_minutes`: 1–30.

Both are set together or both are null; null means no breaks. Breaks fall inside the block's `planned_minutes`. The block resource SHALL return both fields. Invalid values SHALL be rejected with a 422 response.

#### Scenario: Set a break pattern
- **WHEN** a user sets `break_every_minutes` 50 and `break_minutes` 10 on a 150-minute Block A
- **THEN** the block returns that pattern, and its `planned_minutes` stays 150

#### Scenario: Half a pattern
- **WHEN** a user sets `break_minutes` 10 on a block without `break_every_minutes`
- **THEN** the API responds 422 with a validation error on `break_every_minutes`

#### Scenario: Generated blocks
- **WHEN** a plan's blocks are generated from a gear template
- **THEN** they have no break pattern

### Requirement: Blocks with an active timer are locked
While a block's timer is active (running or paused):
- updating its `block_date`, `planned_minutes`, `status`, `break_every_minutes` or `break_minutes` SHALL be rejected with a 422 response;
- deleting the block SHALL be rejected with a 422 response.

Its planned task, topic, category, slot, lane and note SHALL stay editable.

#### Scenario: Mark a running block by hand
- **WHEN** a user sets a running block's status to `done`
- **THEN** the API responds 422 with a validation error on `status`, and the timer keeps running

#### Scenario: Delete a running block
- **WHEN** a user deletes a block whose timer is paused
- **THEN** the API responds 422 and the block and its timer remain

#### Scenario: Edit the task while running
- **WHEN** a user changes a running block's planned task
- **THEN** the change is saved and the timer keeps running

### Requirement: Block editor on the Weekly Plan page
The Weekly Plan page SHALL let a user edit a block in a dialog:
- planned task, planned minutes and note;
- topic, picked from the user's topics;
- break pattern: none, 25 + 5, 50 + 10, or custom values;
- slot and lane.

Fields that are locked while the block's timer is active SHALL be disabled in that case.

#### Scenario: Design a block
- **WHEN** a user edits Saturday's Block A to 120 minutes, topic "Binary trees" and a 50 + 10 break pattern
- **THEN** the block card shows 120 minutes and the topic, and the block returns the new pattern

#### Scenario: Editing a running block
- **WHEN** a user opens the editor of a running block
- **THEN** the minutes, status and break fields are disabled, and the task, topic and note can be changed

### Requirement: Recorded minutes on block cards
A block card on the Weekly Plan page SHALL show its recorded minutes next to its planned minutes once the block has any recorded time.

#### Scenario: Partial block card
- **WHEN** a 90-minute block was stopped after 40 minutes
- **THEN** its card shows "40 / 90 min" and the partial status

## MODIFIED Requirements

### Requirement: Regenerate remaining blocks for a new gear
`POST /api/study/weekly-plan/{week}/regenerate` with a `gear` SHALL:
- set the plan's gear;
- replace the blocks dated today or later that still have status `planned` and no active timer with that gear's template blocks for the same dates.

Past blocks, blocks with any other status and blocks with an active timer SHALL NOT change.

#### Scenario: Downshift mid-week
- **WHEN** on Tuesday a user regenerates a green plan as `yellow`
- **THEN** the planned blocks from Tuesday to Saturday are replaced with yellow-template blocks, while Sunday's and Monday's blocks and any block already marked done stay the same

#### Scenario: Downshift while a block is running
- **WHEN** on Tuesday a user regenerates the plan as `yellow` while Tuesday's morning block timer is running
- **THEN** the morning block and its timer stay, and the other planned blocks from Tuesday on are replaced
