# Spec Delta

## Purpose

Keep one authoritative catalog of the learning techniques and decision algorithms StudyTracker uses. Each entry says what it is, why the app uses it, its benefits, how strong the evidence is, and its research references. The public Features page and the in-app User Guide both present it.

## ADDED Requirements

### Requirement: Technique entries
The catalog SHALL contain one entry for each learning technique the app applies. Each entry has:
- a name and a plain-language description;
- why the app uses it;
- at least two benefits;
- the app features where it appears;
- at least one research reference.

#### Scenario: Retrieval practice entry
- **WHEN** a reader opens the retrieval practice entry
- **THEN** it explains the technique, lists at least two benefits, names the Review page and recall questions as where it is used, and cites at least one reference such as Roediger & Karpicke (2006)

### Requirement: Required techniques
The catalog SHALL include these techniques:
- retrieval practice
- spaced repetition
- successive relearning
- feedback after retrieval
- desirable difficulties and generation
- interleaving
- implementation intentions
- realistic planning
- consistency over perfection
- single-track focus
- learning from errors

#### Scenario: Complete technique list
- **WHEN** the catalog is checked against this list
- **THEN** every listed technique has an entry

### Requirement: Algorithm entries
The catalog SHALL contain one entry for each decision algorithm the app runs. Each entry has:
- a name;
- what it decides, in plain language;
- the rule it follows and a worked example;
- at least two benefits;
- the menus where the user sees it;
- at least one research reference.

#### Scenario: Adaptive scheduler entry
- **WHEN** a reader opens the adaptive scheduler entry
- **THEN** it shows the grade rules, a worked example with dates, its benefits and its references

### Requirement: Required algorithms
The catalog SHALL include these algorithms:
- adaptive review scheduler
- relearn check
- interval presets and exam-date repeat
- due-review queue
- review-time estimate and budget
- review-debt detection
- never-miss-twice prompt
- weekly gear blocks
- weekly consistency score
- mistake schedule and merge

#### Scenario: Complete algorithm list
- **WHEN** the catalog is checked against this list
- **THEN** every listed algorithm has an entry

### Requirement: Evidence labels
Every technique and algorithm SHALL carry exactly one evidence label:
- `Research-backed`: the mechanism has direct experimental or meta-analytic support;
- `Rule of thumb`: a practical heuristic that research motivates but has not tested directly.

#### Scenario: Heuristic labelled honestly
- **WHEN** a reader views the review-debt detection entry
- **THEN** it is labelled `Rule of thumb`

### Requirement: Stated limits
Entries SHALL state the known limits of their cited evidence. The interleaving entry SHALL say that its benefit is largest for similar, easily confused material.

#### Scenario: Interleaving caveat
- **WHEN** a reader views the interleaving entry
- **THEN** it says mixing helps most for similar material and cites Brunmair & Richter (2019)

### Requirement: Reference list
The catalog SHALL keep one reference list in APA style: authors, year, title, venue, and volume, issue and pages where they apply. Every citation SHALL point to an entry in the list, and every entry in the list SHALL be cited at least once.

#### Scenario: Unknown citation
- **WHEN** an entry cites a reference id that is not in the list
- **THEN** the content check reports the error and the production build fails

### Requirement: Verified references only
Each reference SHALL be marked as verified against its original publication record before it is published. An unverified reference SHALL make the content check fail.

#### Scenario: Unverified reference
- **WHEN** a reference is not marked verified
- **THEN** the content check reports it by id

### Requirement: Same content on both pages
The Features page and the User Guide SHALL render catalog entries from the same source, so the text, labels and citations of an entry are identical on both.

#### Scenario: Edited entry
- **WHEN** an entry's benefits are edited
- **THEN** both the Features page and the User Guide show the edited benefits
