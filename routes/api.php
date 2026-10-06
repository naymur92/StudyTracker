<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\StudyTracker\CategoryApiController;
use App\Http\Controllers\Api\StudyTracker\CategoryScheduleApiController;
use App\Http\Controllers\Api\StudyTracker\DashboardApiController;
use App\Http\Controllers\Api\StudyTracker\MistakeApiController;
use App\Http\Controllers\Api\StudyTracker\PracticeLogApiController;
use App\Http\Controllers\Api\StudyTracker\ReportApiController;
use App\Http\Controllers\Api\StudyTracker\ReviewApiController;
use App\Http\Controllers\Api\StudyTracker\RevisionTemplateApiController;
use App\Http\Controllers\Api\StudyTracker\StudyPreferenceApiController;
use App\Http\Controllers\Api\StudyTracker\StudyTaskApiController;
use App\Http\Controllers\Api\StudyTracker\TopicApiController;
use App\Http\Controllers\Api\StudyTracker\WeeklyPlanApiController;
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

        // Review schedules: preset library and per-user category schedules
        Route::get('/schedule-presets', [CategoryScheduleApiController::class, 'presets'])->middleware('throttle:study-read')->name('schedule-presets');
        Route::get('/categories/{category}/schedule', [CategoryScheduleApiController::class, 'show'])->middleware('throttle:study-read')->name('categories.schedule.show');
        Route::put('/categories/{category}/schedule', [CategoryScheduleApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('categories.schedule.update');
        Route::delete('/categories/{category}/schedule', [CategoryScheduleApiController::class, 'destroy'])->middleware(['throttle:study-write', 'deny.demo'])->name('categories.schedule.destroy');

        // Revision Template Configuration (user-level spacing setup)
        Route::get('/revision-templates', [RevisionTemplateApiController::class, 'index'])->middleware('throttle:study-read')->name('revision-templates.index');
        Route::put('/revision-templates', [RevisionTemplateApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('revision-templates.update');
        Route::post('/revision-templates/reset', [RevisionTemplateApiController::class, 'reset'])->middleware(['throttle:study-write', 'deny.demo'])->name('revision-templates.reset');

        // Review load (due-today estimate, budget, review debt)
        Route::get('/review-load', [ReviewApiController::class, 'load'])->middleware('throttle:study-read')->name('review-load');
        Route::get('/review-queue', [ReviewApiController::class, 'queue'])->middleware('throttle:study-read')->name('review-queue');

        // Mistake notebook
        Route::get('/mistakes', [MistakeApiController::class, 'index'])->middleware('throttle:study-read')->name('mistakes.index');
        Route::post('/mistakes', [MistakeApiController::class, 'store'])->middleware(['throttle:study-write', 'deny.demo'])->name('mistakes.store');
        Route::patch('/mistakes/{mistake}', [MistakeApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('mistakes.update');
        Route::delete('/mistakes/{mistake}', [MistakeApiController::class, 'destroy'])->middleware(['throttle:study-write', 'deny.demo'])->name('mistakes.destroy');
        Route::post('/mistakes/{mistake}/merge', [MistakeApiController::class, 'merge'])->middleware(['throttle:study-write', 'deny.demo'])->name('mistakes.merge');

        // Weekly planning (gears, blocks, score)
        Route::get('/weekly-plan/history', [WeeklyPlanApiController::class, 'history'])->middleware('throttle:study-read')->name('weekly-plan.history');
        Route::get('/weekly-plan', [WeeklyPlanApiController::class, 'show'])->middleware('throttle:study-read')->name('weekly-plan.show');
        Route::post('/weekly-plan', [WeeklyPlanApiController::class, 'store'])->middleware(['throttle:study-write', 'deny.demo'])->name('weekly-plan.store');
        Route::patch('/weekly-plan/{week}', [WeeklyPlanApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('weekly-plan.update');
        Route::delete('/weekly-plan/{week}', [WeeklyPlanApiController::class, 'destroy'])->middleware(['throttle:study-write', 'deny.demo'])->name('weekly-plan.destroy');
        Route::post('/weekly-plan/{week}/regenerate', [WeeklyPlanApiController::class, 'regenerate'])->middleware(['throttle:study-write', 'deny.demo'])->name('weekly-plan.regenerate');
        Route::post('/weekly-plan/{week}/blocks', [WeeklyPlanApiController::class, 'storeBlock'])->middleware(['throttle:study-write', 'deny.demo'])->name('weekly-plan.blocks.store');
        Route::patch('/blocks/{block}', [WeeklyPlanApiController::class, 'updateBlock'])->middleware(['throttle:study-write', 'deny.demo'])->name('blocks.update');
        Route::delete('/blocks/{block}', [WeeklyPlanApiController::class, 'destroyBlock'])->middleware(['throttle:study-write', 'deny.demo'])->name('blocks.destroy');

        // Study preferences (review budget, week layout, success line)
        Route::get('/preferences', [StudyPreferenceApiController::class, 'show'])->middleware('throttle:study-read')->name('preferences.show');
        Route::put('/preferences', [StudyPreferenceApiController::class, 'update'])->middleware(['throttle:study-write', 'deny.demo'])->name('preferences.update');
    });
});
