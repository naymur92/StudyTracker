# Spec Delta

## Purpose

Show visitors what StudyTracker does and the learning science behind it. The page covers the features, the techniques they apply, how the app makes its decisions, and the research that supports them.

## ADDED Requirements

### Requirement: Public Features page
The SPA SHALL serve a Features page at `/features` to guests and logged-in users alike. The public header (desktop and mobile) SHALL link to it.

#### Scenario: Guest visit
- **WHEN** a guest opens `/features`
- **THEN** the page loads without asking them to log in

#### Scenario: Logged-in visit
- **WHEN** a logged-in user opens `/features`
- **THEN** the page loads, with no redirect to the dashboard

### Requirement: Section navigation
The Features page SHALL have four sections, each linkable by anchor: Features, Techniques, How StudyTracker decides, and References.

#### Scenario: Jump to references
- **WHEN** a user opens `/features#references`
- **THEN** the page scrolls to the References section

### Requirement: Feature overview reflects current behavior
The Features section SHALL describe the features the app currently offers. Each feature SHALL link to the techniques it applies. It SHALL NOT say that revisions follow a fixed schedule.

#### Scenario: Adaptive reviews described
- **WHEN** a visitor reads the feature overview
- **THEN** it describes reviews that adapt to recall grades, and does not present 1·7·30·90 days as the only schedule

### Requirement: Techniques section
The Techniques section SHALL show every catalog technique with its description, why the app uses it, its benefits, its evidence label and in-text citations.

#### Scenario: Citation link
- **WHEN** a visitor selects the citation "Roediger & Karpicke, 2006" on a technique
- **THEN** the page moves to that entry in the References section

### Requirement: How StudyTracker decides section
The "How StudyTracker decides" section SHALL show every catalog algorithm with its rule, a worked example, its benefits, its evidence label and its citations.

#### Scenario: Scheduler explained
- **WHEN** a visitor reads the adaptive scheduler card
- **THEN** it shows what Again, Hard, Good and Easy each do, with a dated example

### Requirement: References section
The References section SHALL list every cited reference in APA style, sorted alphabetically by first author, each with its own anchor.

#### Scenario: Alphabetical list
- **WHEN** a visitor reads the References section
- **THEN** the entries are sorted by first author's surname

### Requirement: Home and About summaries
The feature summary shown on the Home and About pages SHALL describe adaptive reviews and link to the Features page. The About page SHALL NOT call the schedule fixed.

#### Scenario: Home page link
- **WHEN** a visitor reads the home page summary
- **THEN** it links to "All features and the science" at `/features`
