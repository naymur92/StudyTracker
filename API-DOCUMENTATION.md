# Study Tracker API Documentation

## Overview

The Study Tracker API is a REST API built with Laravel 12 and Laravel Passport (OAuth 2.0 password grant).

- Base URL: `http://your-domain.com/api`
- Response wrapper: `flag`, `msg`, `data`, `response_code`
- IDs in API payloads are encoded strings (not raw DB integers)

---

## Authentication Flow

### Registration and Email Verification

1. Register using `POST /api/auth/register`
2. User is created with `is_active = false`
3. Verification email is sent with token link
4. Verify with `GET /api/auth/verify-email?email=...&token=...`
5. Account becomes active and verified
6. Login token is only issued for active + verified users

### Headers

Token endpoints (`/api/auth/token`, `/api/auth/token/refresh`):

```http
Accept: application/json
Content-Type: application/json
X-Client-Id: {passport_password_grant_client_id}
X-Client-Secret: {passport_password_grant_client_secret}
```

Register/verify endpoints (`/api/auth/register`, `/api/auth/verify-email`, `/api/auth/resend-verification`):

```http
Accept: application/json
Content-Type: application/json
```

Forgot password endpoints (`/api/auth/forgot-password/request`, `/api/auth/forgot-password/verify`):

```http
Accept: application/json
Content-Type: application/json
```

Protected endpoints (`/api/study/*`, `/api/user`):

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {access_token}
```

---

## Auth Endpoints

### 1. Register

`POST /api/auth/register`

Request body:

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "Password@123",
    "password_confirmation": "Password@123"
}
```

Response (201):

```json
{
    "flag": true,
    "msg": "Registration successful. Please verify your email to activate your account.",
    "data": {
        "name": "John Doe",
        "email": "john@example.com",
        "is_active": false,
        "email_verification_sent": true
    },
    "response_code": 201
}
```

### 2. Verify Email

`GET /api/auth/verify-email?email=john@example.com&token={verification_token}`

Response (200):

```json
{
    "flag": true,
    "msg": "Email verified successfully. Your account is now active.",
    "data": [],
    "response_code": 200
}
```

### 3. Resend Verification Email

`POST /api/auth/resend-verification`

Request body:

```json
{
    "email": "john@example.com"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Verification email sent successfully.",
    "data": [],
    "response_code": 200
}
```

### 4. Get Access Token

`POST /api/auth/token`

Request body:

```json
{
    "email": "john@example.com",
    "password": "Password@123"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Success",
    "token_type": "Bearer",
    "expires_in": 10800,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "def50200abc123...",
    "data": [],
    "response_code": 200
}
```

### 5. Refresh Access Token

`POST /api/auth/token/refresh`

Request body:

```json
{
    "refresh_token": "{your_refresh_token}"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Success",
    "token_type": "Bearer",
    "expires_in": 10800,
    "access_token": "eyJ0eXAi...",
    "refresh_token": "def50200...",
    "data": [],
    "response_code": 200
}
```

### 6. Forgot Password - Request Code

`POST /api/auth/forgot-password/request`

Request body:

```json
{
    "email": "john@example.com"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "If this email exists, a password reset code has been sent.",
    "data": [],
    "response_code": 200
}
```

Notes:

- Password reset code email can be generated only once every 30 minutes per user.
- Code expires in 30 minutes.

### 7. Forgot Password - Verify Code and Reset Password

`POST /api/auth/forgot-password/verify`

Request body:

```json
{
    "email": "john@example.com",
    "code": "123456",
    "password": "NewPassword@123",
    "password_confirmation": "NewPassword@123"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Password reset successful. You can now login with your new password.",
    "data": [],
    "response_code": 200
}
```

Rules:

- A code can be used only once.
- Successful password resets are limited to 5 times per user per month.

---

## User Endpoint

### Get User Profile

`GET /api/user`

Response (200):

```json
{
    "flag": true,
    "msg": "User profile fetched successfully.",
    "data": {
        "id": "a9k31mQz",
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-03-27 12:00:00",
        "email_verified_at": "2026-03-27 12:30:00",
        "is_active": true,
        "status": "verified",
        "topics_count": 12,
        "tasks_count": 34,
        "practice_logs_count": 8
    },
    "response_code": 200
}
```

### Update User Profile

`PATCH /api/user`

Request body:

```json
{
    "name": "John Updated"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Profile updated successfully.",
    "data": {
        "id": "a9k31mQz",
        "name": "John Updated",
        "email": "john@example.com",
        "created_at": "2026-03-27 12:00:00",
        "email_verified_at": "2026-03-27 12:30:00",
        "is_active": true,
        "status": "verified"
    },
    "response_code": 200
}
```

Constraint:

- Email cannot be changed via profile update API.

### Change Password (Authenticated)

`POST /api/user/change-password`

Request body:

```json
{
    "current_password": "Password@123",
    "new_password": "NewPassword@123",
    "new_password_confirmation": "NewPassword@123"
}
```

Response (200):

```json
{
    "flag": true,
    "msg": "Password changed successfully.",
    "data": [],
    "response_code": 200
}
```

---

## Study Endpoints

### Dashboard

- `GET /api/study/dashboard?date=YYYY-MM-DD`
- `GET /api/study/calendar?year=YYYY&month=MM`

### Categories

- `GET /api/study/categories`
- `POST /api/study/categories`
- `PUT/PATCH /api/study/categories/{category}`
- `DELETE /api/study/categories/{category}`

### Topics

- `GET /api/study/topics`
- `POST /api/study/topics`
- `GET /api/study/topics/{topic}`
- `PUT/PATCH /api/study/topics/{topic}`
- `DELETE /api/study/topics/{topic}`

### Study Tasks

- `GET /api/study/daily-tasks`
- `POST /api/study/tasks/{task}/complete`
- `POST /api/study/tasks/{task}/skip`
- `POST /api/study/tasks/{task}/reschedule`

### Practice Logs

- `GET /api/study/practice-logs`
- `POST /api/study/practice-logs`
- `PUT/PATCH /api/study/practice-logs/{practiceLog}`
- `DELETE /api/study/practice-logs/{practiceLog}`

---

## Encoded ID Notes

All IDs returned by resource-based responses are encoded strings.

Examples:

- `id: "a9k31mQz"`
- `topic_id: "b2XpL0qR"`
- `category_id: "kL9m2Dq7"`

When sending IDs back to the API (path params or filter/body fields like `topic_id`, `task_id`, `category_id`), send the encoded value from responses.

---

## Common Request Samples

### Create Topic

`POST /api/study/topics`

```json
{
    "category_id": "kL9m2Dq7",
    "title": "Derivatives and Differentiation",
    "description": "Understanding derivatives",
    "source_link": "https://example.com/derivatives",
    "difficulty": "medium",
    "first_study_date": "2026-03-27",
    "notes": "Start with basic rules",
    "tags": ["calculus", "derivatives"]
}
```

### Mark Task Complete

`POST /api/study/tasks/{task}/complete`

```json
{
    "notes": "Completed successfully, understood all concepts",
    "difficulty_feedback": "medium"
}
```

### Create Practice Log

`POST /api/study/practice-logs`

```json
{
    "topic_id": "b2XpL0qR",
    "task_id": "x1ZpV88n",
    "practiced_on": "2026-03-27",
    "practice_type": "problem_solving",
    "details": "Solved 10 problems from chapter 5",
    "duration_minutes": 45,
    "outcome": "Got 8/10 correct"
}
```

---

## Response Format

Success:

```json
{
    "flag": true,
    "msg": "Success message",
    "data": {},
    "response_code": 200
}
```

Validation error:

```json
{
    "flag": false,
    "msg": "The email field is required.",
    "errors": {
        "email": ["The email field is required."]
    },
    "data": null,
    "response_code": 422
}
```

---

## Rate Limiting

| Limiter         | Routes                         | Limit  | Scope    |
| --------------- | ------------------------------ | ------ | -------- |
| `auth-register` | `/api/auth/register`           | 5/min  | Per IP   |
| `auth-token`    | `/api/auth/token`              | 8/min  | Per IP   |
| `auth-refresh`  | `/api/auth/token/refresh`      | 20/min | Per IP   |
| `auth-verify`   | verify + resend verification   | 6/min  | Per IP   |
| `auth-forgot`   | forgot password request/verify | 5/min  | Per IP   |
| `study-read`    | `GET /api/study/*`             | 60/min | Per user |
| `study-write`   | `POST/PUT/DELETE /api/study/*` | 30/min | Per user |
| `api-profile`   | `/api/user*`                   | 30/min | Per user |

---

## Resource Field Map (Current)

- `UserResource`: `id`, `name`, `email`, `email_verified_at`, `is_active`, `status`
- `CategoryResource`: `id`, `name`, `color`, `icon`, `user_id`, `is_system`, `topic_count`, timestamps
- `TopicResource`: `id`, `user_id`, `category_id`, `title`, `slug`, `description`, `source_link`, `difficulty`, `status`, `first_study_date`, `notes`, `tags`, `category`, counts, timestamps
- `StudyTaskResource`: `id`, `user_id`, `topic_id`, `title`, `task_type`, `task_type_label`, `revision_no`, `scheduled_date`, `status`, completion/lock flags, `parent_task_id`, `notes`, `difficulty_feedback`, `topic`, timestamps
- `PracticeLogResource`: `id`, `user_id`, `topic_id`, `task_id`, `practiced_on`, `practice_type`, `practice_type_label`, `details`, `duration_minutes`, `outcome`, `topic`, `task`, timestamps

---

## Postman Collection

Use the updated collection:

- `StudyTracker-API.postman_collection.json`

Collection includes:

- Auth verification flow (`register`, `verify-email`, `resend-verification`)
- Forgot password flow (`forgot-password/request`, `forgot-password/verify`)
- Encoded ID variables (`category_id`, `topic_id`, `task_id`, `practice_log_id`)
- User profile endpoints (`get`, `patch`, `change-password`)
