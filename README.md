# StudyTracker

A full-featured **spaced repetition study management** application built with Laravel 12. Track topics, manage revision schedules, log practice sessions, and monitor progress — all backed by a REST API with OAuth 2.0 authentication and a comprehensive admin panel.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Admin Panel](#admin-panel)
- [Scheduled Commands](#scheduled-commands)
- [Rate Limiting](#rate-limiting)
- [Security](#security)
- [License](#license)

---

## Features

### Study Tracker (API)

- **Topic Management** — Create, update, archive, and browse study topics with categories, difficulty levels, tags, and source links
- **Spaced Repetition Engine** — Automatically generates revision tasks at Day +1, +7, +30, +90 based on the Ebbinghaus forgetting curve
- **Customizable Revision Templates** — System defaults with per-user override support for custom revision schedules
- **Daily Agenda** — Grouped daily task view (Learn → Revision 1–4 → Practice → Overdue) with completion summary
- **Task Actions** — Complete, skip, or reschedule tasks with difficulty feedback and notes
- **Date Locking** — Completed tasks become immutable (date and status locked)
- **Practice Logs** — Log study sessions with type (problem solving, implementation, reading, note making, mock interview), duration, and outcomes
- **Calendar View** — Monthly calendar with per-day task completion/pending/overdue counts
- **Dashboard Stats** — Total/active topics, today's pending, overdue count, daily completions, streak calculator
- **Category System** — User-created and system-wide categories with color and icon support
- **Streak Tracking** — Consecutive day completion streak calculated efficiently in a single query
- **Pagination & Filtering** — All list endpoints support pagination, search, and multi-field filtering

### Authentication & Authorization

- **OAuth 2.0** — Laravel Passport with password grant for API token-based authentication
- **Token Lifecycle** — Access tokens (3 hours), refresh tokens (15 days), personal access tokens (6 months)
- **Login Tracking** — Records successful/failed OAuth logins with IP and device info
- **Role-Based Access Control** — Spatie Laravel Permission with roles and permissions
- **Admin Middleware** — Type-based access control (Super Admin, Admin, User, API User)

### Admin Panel

- **Dashboard** — System-wide overview with key metrics
- **Study Tracker Overview** — Aggregate stats, 14-day completion chart (Chart.js), top users summary
- **Trend & Stats Reports** — Date-range completion trends, task type breakdown, top topics, practice type analysis, most active users
- **Users Report** — Paginated list of all type-3 users with topic counts, task stats, practice log counts (search + sort)
- **Per-User Deep Report** — Individual user analysis: topics, task completion rate, recent practice logs, upcoming tasks
- **Topics Report** — All topics with task/practice counts, filterable by status, difficulty, and search
- **Categories Report** — System vs user categories with topic counts and task completion stats
- **Tasks Report** — Advanced task management with 7 filters (status, type, user, topic, date range, sort)
- **User Management** — Create/edit users, change status, reset passwords
- **Role & Permission Management** — Full CRUD for roles and permissions
- **OAuth Client Management** — Manage Passport password grant clients, regenerate secrets
- **Activity Logs** — Comprehensive audit trail with cleanup
- **Login History** — Browse all user login records
- **System Logs** — View, download, and delete Laravel log files
- **Settings** — Configurable site settings with bulk update
- **Backup & Restore** — Database backup, download, restore, and delete
- **Cache Management** — Clear config/route/view/all caches, optimize

### Infrastructure

- **Standardized API Responses** — All responses use `CustomResponseTrait` with consistent `{flag, msg, data, response_code}` format
- **API Resource Classes** — `TopicResource`, `StudyTaskResource`, `PracticeLogResource`, `CategoryResource`, `UserResource`
- **Form Request Validation** — Dedicated request classes with user-scoped `exists` rules
- **Service Layer** — Business logic separated into service classes (`CreateTopicWithPlanService`, `GenerateRevisionTasksService`, `CompleteTaskService`, `BuildDailyAgendaService`, `AddPracticeLogService`)
- **Exception Handling** — API-specific renderers for 404, 403, 401, and general HTTP exceptions
- **Rate Limiting** — Per-route named rate limiters optimized for AWS free tier
- **Soft Deletes** — Topics and categories support soft deletion
- **Scheduled Tasks** — Automatic overdue task marking via `study:mark-missed`

---

## Tech Stack

| Layer            | Technology                                  |
| ---------------- | ------------------------------------------- |
| Framework        | Laravel 12.x                                |
| PHP              | 8.2+                                        |
| Database         | MySQL 5.7+ / MariaDB 10.3+                  |
| Authentication   | Laravel Passport (OAuth 2.0 Password Grant) |
| Authorization    | Spatie Laravel Permission                   |
| Admin UI         | SB Admin 2, Bootstrap 4, Chart.js 4.4       |
| Frontend Build   | Vite 7, Sass                                |
| Device Detection | jenssegers/agent                            |
| Notifications    | PHP Flasher                                 |

---

## Architecture

```
app/
├── Console/Commands/         # Artisan commands (study:mark-missed)
├── Exceptions/               # Custom exception handler with API renderers
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController          # OAuth token issue/refresh
│   │   │   ├── UserApiController       # User profile endpoint
│   │   │   └── StudyTracker/           # 5 API controllers
│   │   └── AdminStudyTrackerController # Admin reports & overview
│   ├── Middleware/            # ApiHeadersCheck, TokenApiHeadersCheck, AdminMiddleware
│   ├── Requests/StudyTracker/ # 6 form request classes
│   └── Resources/            # 5 API resource classes
├── Models/                   # 10 Eloquent models
├── Services/StudyTracker/    # 5 service classes
├── Traits/                   # CustomResponseTrait, HasFiles
└── Providers/                # Rate limiters, Passport config
```

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM
- Apache/Nginx

---

## Installation

```bash
# Clone the repository
git clone https://github.com/naymur92/StudyTracker.git
cd StudyTracker

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed default data (admin user, permissions, revision templates)
php artisan db:seed

# Create Passport encryption keys
php artisan passport:keys

# Create Passport password grant client
php artisan passport:client --password

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

---

## Configuration

### Environment Variables

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=study_tracker
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
```

### Scheduler (Production)

Add to crontab for automatic overdue task marking:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## API Documentation

Full API reference with request/response examples:

> **[API-DOCUMENTATION.md](API-DOCUMENTATION.md)**

### Postman Collection

Import the ready-to-use Postman collection for testing all 22 API endpoints:

> **[StudyTracker-API.postman_collection.json](StudyTracker-API.postman_collection.json)**

After importing, configure these Postman collection variables:

| Variable        | Description                           | Example                           |
| --------------- | ------------------------------------- | --------------------------------- |
| `url`           | API base URL                          | `http://localhost:8000`           |
| `token`         | Bearer access token                   | _(from /api/auth/token response)_ |
| `client_id`     | Passport password grant client ID     | `1`                               |
| `client_secret` | Passport password grant client secret | `abc123...`                       |

### API Endpoints Quick Reference

| Method   | Endpoint                           | Description                    | Rate Limit  |
| -------- | ---------------------------------- | ------------------------------ | ----------- |
| `POST`   | `/api/auth/token`                  | Get access token               | 8/min/IP    |
| `POST`   | `/api/auth/token/refresh`          | Refresh access token           | 20/min/IP   |
| `GET`    | `/api/user`                        | Authenticated user profile     | 30/min/user |
| `GET`    | `/api/study/dashboard`             | Dashboard stats + daily agenda | 60/min/user |
| `GET`    | `/api/study/calendar`              | Monthly calendar view          | 60/min/user |
| `GET`    | `/api/study/daily-tasks`           | Daily task agenda              | 60/min/user |
| `POST`   | `/api/study/tasks/{id}/complete`   | Complete a task                | 30/min/user |
| `POST`   | `/api/study/tasks/{id}/skip`       | Skip a task                    | 30/min/user |
| `POST`   | `/api/study/tasks/{id}/reschedule` | Reschedule a task              | 30/min/user |
| `GET`    | `/api/study/topics`                | List topics (paginated)        | 60/min/user |
| `POST`   | `/api/study/topics`                | Create topic + revision plan   | 30/min/user |
| `GET`    | `/api/study/topics/{id}`           | Topic detail with tasks & logs | 60/min/user |
| `PUT`    | `/api/study/topics/{id}`           | Update topic                   | 30/min/user |
| `DELETE` | `/api/study/topics/{id}`           | Archive topic (soft delete)    | 30/min/user |
| `GET`    | `/api/study/practice-logs`         | List practice logs (paginated) | 60/min/user |
| `POST`   | `/api/study/practice-logs`         | Create practice log            | 30/min/user |
| `PUT`    | `/api/study/practice-logs/{id}`    | Update practice log            | 30/min/user |
| `DELETE` | `/api/study/practice-logs/{id}`    | Delete practice log            | 30/min/user |
| `GET`    | `/api/study/categories`            | List categories                | 60/min/user |
| `POST`   | `/api/study/categories`            | Create category                | 30/min/user |
| `PUT`    | `/api/study/categories/{id}`       | Update category                | 30/min/user |
| `DELETE` | `/api/study/categories/{id}`       | Delete category (soft delete)  | 30/min/user |

---

## Admin Panel

Access the admin panel at `/admin` after logging in with a Super Admin (type 1) or Admin (type 2) account.

### Admin Routes

| Route                                    | Description                                 |
| ---------------------------------------- | ------------------------------------------- |
| `/admin`                                 | Dashboard                                   |
| `/admin/study-tracker`                   | Study Tracker overview with aggregate stats |
| `/admin/study-tracker/reports`           | Trends & stats with date-range filtering    |
| `/admin/study-tracker/users-report`      | All type-3 users with study stats           |
| `/admin/study-tracker/users/{id}`        | Per-user deep report                        |
| `/admin/study-tracker/topics-report`     | All topics with completion stats            |
| `/admin/study-tracker/categories-report` | Category analysis                           |
| `/admin/study-tracker/tasks-report`      | Advanced task management                    |
| `/admin/users`                           | User management (CRUD, status, password)    |
| `/admin/roles`                           | Role management                             |
| `/admin/permissions`                     | Permission management                       |
| `/admin/oauth-clients`                   | OAuth client management                     |
| `/admin/activity-logs`                   | Activity audit trail                        |
| `/admin/login-history`                   | Login history                               |
| `/admin/system-logs`                     | Laravel log viewer                          |
| `/admin/settings`                        | Application settings                        |
| `/admin/backups`                         | Backup & restore                            |
| `/admin/cache/info`                      | Cache management                            |

---

## Scheduled Commands

| Command             | Schedule       | Description                             |
| ------------------- | -------------- | --------------------------------------- |
| `study:mark-missed` | Daily at 00:05 | Marks overdue pending tasks as `missed` |

Run manually:

```bash
php artisan study:mark-missed
```

---

## Rate Limiting

Optimized for low-resource deployments (AWS free tier):

| Limiter        | Limit           | Scope                  |
| -------------- | --------------- | ---------------------- |
| `auth-token`   | 8 requests/min  | Per IP                 |
| `auth-refresh` | 20 requests/min | Per IP                 |
| `study-read`   | 60 requests/min | Per authenticated user |
| `study-write`  | 30 requests/min | Per authenticated user |
| `api-profile`  | 30 requests/min | Per authenticated user |

---

## Security

- OAuth 2.0 password grant with JWT tokens
- Scoped validation rules — users can only reference their own resources
- Automatic date locking on task completion
- Role-based admin access (type 1 & 2 only)
- Custom API middleware for header validation (`Accept`, `Content-Type`, `X-Client-Id`, `X-Client-Secret`)
- Rate limiting on all API endpoints
- Soft deletes preserve data integrity
- CSRF protection on web routes
- Password hashing via bcrypt

---

## Database Schema

```
users ─┬─< topics ─┬─< study_tasks ──< practice_logs
       │            │
       │            └─< practice_logs
       │
       ├─< categories ──< topics
       │
       └─< topic_revision_templates
```

**5 Study Tracker tables:** `categories`, `topics`, `topic_revision_templates`, `study_tasks`, `practice_logs`

---

## License

This project is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).
