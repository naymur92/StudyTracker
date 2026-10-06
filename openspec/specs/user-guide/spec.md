# user-guide Specification

## Purpose

Give logged-in users a manual for every menu: what each one is for, how to use it step by step, and the techniques and algorithms behind it, with their benefits and research references.

## Requirements

### Requirement: User Guide page
The SPA SHALL serve a User Guide at `/app/guide` inside the app layout, with a "User Guide" navigation item. Demo users SHALL be able to read it. Guests SHALL be redirected the same way as for other app pages.

#### Scenario: Open the guide
- **WHEN** a logged-in user selects "User Guide" in the navigation
- **THEN** the guide opens inside the app layout

#### Scenario: Guest access
- **WHEN** a guest opens `/app/guide`
- **THEN** they are redirected away, as for other `/app` pages

### Requirement: One section per menu
The guide SHALL have one section for every navigation item, plus "Getting started" and "Daily and weekly routine". Each menu section SHALL give the menu's purpose, numbered steps for using it, and tips.

#### Scenario: Every menu covered
- **WHEN** the content check compares the navigation with the guide
- **THEN** every navigation item has a guide section, or the check fails

### Requirement: Science behind each menu
Each menu section SHALL list the catalog algorithms and techniques that menu relies on, with their benefits, evidence labels and citations.

#### Scenario: Review section
- **WHEN** a user reads the Review section
- **THEN** it shows the adaptive scheduler, the relearn check and the due-review queue, with benefits and references

### Requirement: Table of contents and deep links
The guide SHALL show a table of contents. Each section SHALL have an anchor, and opening the guide with that anchor SHALL scroll to the section.

#### Scenario: Deep link
- **WHEN** a user opens `/app/guide#review`
- **THEN** the page scrolls to the Review section

### Requirement: Help links on app pages
Each app page reachable from the navigation SHALL show a "Guide" link to its own guide section.

#### Scenario: Help from Daily Tasks
- **WHEN** a user selects "Guide" on the Daily Tasks page
- **THEN** the guide opens at the Daily Tasks section

### Requirement: References inside the guide
The guide SHALL list in full every reference it cites, so users can read a citation without leaving the app.

#### Scenario: Citation in the guide
- **WHEN** a user selects a citation in the guide
- **THEN** the guide moves to that reference in its own reference list

### Requirement: Getting-started pointer
When the user has no topics yet, the Dashboard SHALL link to the guide's Getting started section.

#### Scenario: New user
- **WHEN** a user with no topics opens the dashboard
- **THEN** a "New here? Read Getting started" link to `/app/guide#getting-started` is shown
