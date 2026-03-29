<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\StudyTracker\CategoryApiController;
use App\Http\Controllers\Api\StudyTracker\DashboardApiController;
use App\Http\Controllers\Api\StudyTracker\PracticeLogApiController;
use App\Http\Controllers\Api\StudyTracker\ReportApiController;
use App\Http\Controllers\Api\StudyTracker\RevisionTemplateApiController;
use App\Http\Controllers\Api\StudyTracker\StudyTaskApiController;
use App\Http\Controllers\Api\StudyTracker\TopicApiController;
use Illuminate\Support\Facades\Route;

// Authorization Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware(['throttle:auth-register', 'api.headers']);
    Route::post('/token', [AuthController::class, 'issueToken'])->middleware(['throttle:auth-token', 'api.token.headers']);
    Route::post('/token/refresh', [AuthController::class, 'refresh'])->middleware(['throttle:auth-refresh', 'api.token.headers']);
    Route::post('/demo-login', [AuthController::class, 'demoLogin'])->middleware(['throttle:auth-token', 'api.token.headers']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware(['throttle:auth-verify', 'api.headers']);
    Route::post('/forgot-password/request', [AuthController::class, 'requestForgotPassword'])->middleware(['throttle:auth-forgot', 'api.headers']);
    Route::post('/forgot-password/verify', [AuthController::class, 'verifyForgotPassword'])->middleware(['throttle:auth-forgot', 'api.headers']);
});


// Protected routes
Route::middleware('auth:api')->group(function () {

    Route::get('/user', [UserApiController::class, 'profile'])->middleware('throttle:api-profile');
    Route::patch('/user', [UserApiController::class, 'updateProfile'])->middleware(['throttle:api-profile', 'deny.demo']);
    Route::post('/user/change-password', [UserApiController::class, 'changePassword'])->middleware(['throttle:api-profile', 'deny.demo']);

    // ─────────────────────────────────────────────────────
    // Study Tracker API  (prefix: /api/study/)
    // ─────────────────────────────────────────────────────
    Route::prefix('study')->name('api.study.')->group(function () {

        // Dashboard & Calendar
        Route::get('/dashboard',   [DashboardApiController::class, 'index'])->middleware('throttle:study-read')->name('dashboard');
        Route::get('/calendar',    [DashboardApiController::class, 'calendar'])->middleware('throttle:study-read')->name('calendar');
        Route::get('/reports/download', [ReportApiController::class, 'download'])->middleware('throttle:study-read')->name('reports.download');
        Route::post('/reports/email', [ReportApiController::class, 'queueEmail'])->middleware(['throttle:study-write', 'deny.demo'])->name('reports.email');

        // Daily task agenda for a date: GET /api/study/daily-tasks?date=YYYY-MM-DD
        Route::get('/daily-tasks', [StudyTaskApiController::class, 'daily'])->middleware('throttle:study-read')->name('daily-tasks');

        // Task actions
        Route::post('/tasks/{task}/complete',   [StudyTaskApiController::class, 'complete'])->middleware('throttle:study-write')->name('tasks.complete');
        Route::post('/tasks/{task}/skip',       [StudyTaskApiController::class, 'skip'])->middleware('throttle:study-write')->name('tasks.skip');
        Route::post('/tasks/{task}/reschedule', [StudyTaskApiController::class, 'reschedule'])->middleware('throttle:study-write')->name('tasks.reschedule');

        // Topics CRUD
        Route::get('/topics', [TopicApiController::class, 'index'])->middleware('throttle:study-read')->name('topics.index');
        Route::post('/topics', [TopicApiController::class, 'store'])->middleware(['throttle:study-write', 'deny.demo'])->name('topics.store');
        Route::get('/topics/{topic}', [TopicApiController::class, 'show'])->middleware('throttle:study-read')->name('topics.show');
        Route::match(['put', 'patch'], '/topics/{topic}', [TopicApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('topics.update');
        Route::delete('/topics/{topic}', [TopicApiController::class, 'destroy'])->middleware(['throttle:study-write', 'deny.demo'])->name('topics.destroy');

        // Practice Logs
        Route::get('/practice-logs', [PracticeLogApiController::class, 'index'])->middleware('throttle:study-read')->name('practice-logs.index');
        Route::post('/practice-logs', [PracticeLogApiController::class, 'store'])->middleware(['throttle:study-write', 'deny.demo'])->name('practice-logs.store');
        Route::match(['put', 'patch'], '/practice-logs/{practiceLog}', [PracticeLogApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('practice-logs.update');
        Route::delete('/practice-logs/{practiceLog}', [PracticeLogApiController::class, 'destroy'])->middleware(['throttle:study-write', 'deny.demo'])->name('practice-logs.destroy');

        // Categories
        Route::get('/categories', [CategoryApiController::class, 'index'])->middleware('throttle:study-read')->name('categories.index');
        Route::post('/categories', [CategoryApiController::class, 'store'])->middleware(['throttle:study-write', 'deny.demo'])->name('categories.store');
        Route::match(['put', 'patch'], '/categories/{category}', [CategoryApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryApiController::class, 'destroy'])->middleware(['throttle:study-write', 'deny.demo'])->name('categories.destroy');

        // Revision Template Configuration (user-level spacing setup)
        Route::get('/revision-templates', [RevisionTemplateApiController::class, 'index'])->middleware('throttle:study-read')->name('revision-templates.index');
        Route::put('/revision-templates', [RevisionTemplateApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('revision-templates.update');
        Route::post('/revision-templates/reset', [RevisionTemplateApiController::class, 'reset'])->middleware(['throttle:study-write', 'deny.demo'])->name('revision-templates.reset');
    });
});
