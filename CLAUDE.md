# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer dev                 # Full dev stack: artisan serve + queue:listen (emails,default) + pail logs + vite, concurrently
php artisan serve            # Backend only (http://localhost:8000)
npm run dev                  # Vite dev server only (port 5173, HMR)
npm run build                # Production asset build (VITE_* env vars are baked in at build time)

composer test                # config:clear + php artisan test
php artisan test --filter=BuildStudyReportServiceTest   # Single test class/method
vendor/bin/pint              # Code formatting (Laravel Pint, default preset)

composer setup               # First-time setup (install, key, migrate, npm build)
php artisan db:seed          # Admin user, permissions, settings, revision templates
php artisan passport:keys && php artisan passport:client --password   # Required for API auth
```

Tests run on in-memory SQLite with sync queues (forced in `phpunit.xml`), regardless of `.env`. There are almost no tests yet — `tests/Unit/BuildStudyReportServiceTest.php` is the real example to follow.

CI (`.github/workflows/deploy.yml`) runs `npm run build` + `php artisan test` on PRs; pushes to `main` auto-deploy to EC2 (via Cloudflare Tunnel SSH, running `deploy/deploy.sh`). Scheduled commands: `study:mark-missed` (daily 00:01, marks overdue tasks), `study:snapshot-review-load` (daily 00:03, start-of-day review load for review-debt detection) and `demo:reset` (daily 00:05), registered in `routes/console.php`.

## Architecture

Laravel 12 monolith serving three distinct surfaces:

1. **REST API** (`routes/api.php`, prefix `/api`) — consumed by the Vue SPA and external clients. Auth is Laravel Passport OAuth2 **password grant** (Passport's own routes are disabled via `Passport::ignoreRoutes()`; token issuing goes through `Api/AuthController` at `/api/auth/token`). Token lifetimes: access 3h, refresh 15d (set in `AppServiceProvider`).
2. **Blade admin panel** (`routes/web.php`, prefix `/admin`) — session auth + `admin` middleware (user `type` 1/2), SB Admin 2 / Bootstrap 4 UI, entry `resources/js/admin/bootstrap.js`.
3. **Vue 3 SPA** (`resources/js/`) — served by the catch-all route `/{vue_route?}` (everything except `api|admin`) returning `view('app')`. Vue Router + Pinia (persisted) + Tailwind. `@` aliases to `resources/js`. Stores in `resources/js/stores/` mirror the API domains; API base URL and OAuth client credentials come from `VITE_API_URL`, `VITE_OAUTH_CLIENT_ID`, `VITE_OAUTH_CLIENT_SECRET`.

Middleware aliases are registered in `bootstrap/app.php`. `api.headers` is appended to the whole API group; `log.activity` to web.

### API conventions (follow these for any new endpoint)

- **Response shape**: every API response goes through `CustomResponseTrait::jsonResponse()` → `{flag, msg, data, response_code}`. Never return bare JSON.
- **Hashed IDs**: models exposed over the API (`Topic`, `StudyTask`, `PracticeLog`, `Category`, `EmailedStudyReport`, `StudyWeek`, `StudyBlock`) use the `HashesIds` trait — route model binding accepts opaque base-62 strings produced by `App\Services\IdHasher` (numeric IDs still pass through for admin use). API Resources emit hashed IDs; Form Requests decode them for `exists` checks. `HASHIDS_SALT` must never change in production.
- **Layers**: Controller (`app/Http/Controllers/Api/StudyTracker/`) → Form Request (`app/Http/Requests/StudyTracker/`, with user-scoped validation) → Service (`app/Services/StudyTracker/`) → API Resource (`app/Http/Resources/`). Business logic lives in services, not controllers.
- **Rate limiting**: every route names a limiter defined in `AppServiceProvider` (`study-read` 60/min, `study-write` 30/min, `auth-*` 5–20/min). New routes must attach one.
- **Demo user**: write endpoints carry the `deny.demo` middleware, which blocks users with `is_demo = true`. Apply it to any new mutating route.
- Exceptions are rendered as API-format JSON by `app/Exceptions/Handler.php` (bound as singleton in `AppServiceProvider`).

### Domain core: spaced repetition

`CreateTopicWithPlanService` resolves the topic's schedule (`ResolveScheduleService`: user's category schedule → user/system `TopicRevisionTemplate` → built-in +1/+7/+30/+90), snapshots it on the topic (`srs_offsets`, repeat columns) and calls `GenerateRevisionTasksService` to create the pending revisions. Scheduling decisions live in the pure `Scheduling\RecallScheduler` (Leitner-style steps: `srs_step` = steps passed; again/hard/good/easy). `CompleteTaskService` locks completed tasks (date and status become immutable); a graded revision (`recall_grade`) re-plans the topic's other pending/missed revisions via `ReplanRevisionsService`, an ungraded one only advances the step, and a late Learn completion re-anchors the plan. Legacy topics without a snapshot adopt one lazily (`TopicScheduleResolver::ensure`). `BuildDailyAgendaService` groups a day's tasks (Learn → Revisions → Practice → Overdue). Users have a `type` (1 Super Admin, 2 Admin, 3 User, 4 API User) plus Spatie roles/permissions for the admin panel.

### Misc

- `app/helpers.php` (composer-autoloaded) provides globals `logActivity()` and `trackLogin()`.
- Queued mail jobs run on the `emails` queue — locally covered by `composer dev`, otherwise run `php artisan queue:listen --queue=emails,default`.
- The repo uses OpenSpec (`openspec/`, `/opsx:*` commands) for proposing and tracking larger changes.
- `README.md` and `API-DOCUMENTATION.md` document all endpoints; keep them in sync when adding routes.
- The public Features page (`/features`) and the in-app User Guide (`/app/guide`) are built from `resources/js/content/learningScience.js`, `userGuide.js` and `features.js`. When app behaviour changes, update the matching technique/algorithm entry and guide section; a new sidebar item (in `resources/js/config/navigation.js`) needs a guide section. Only add references after checking them against the publication record (`verified: true`). `npm run check:content` runs as `prebuild` and fails the build on content errors.
