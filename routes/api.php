<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\JobOfferController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\WorkerProfileController;
use App\Http\Controllers\Api\EarningsController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth Routes ──────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {

    // POST /api/auth/register
    Route::post('/register', [AuthController::class, 'register']);

    // POST /api/auth/login
    Route::post('/login', [AuthController::class, 'login']);

    // POST /api/auth/refresh
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // POST /api/auth/forgot-password
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp']);

    // POST /api/auth/reset-password
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// ─── Protected Routes (JWT required) ────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // ─── Auth ────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::get('/me',   [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // ─── Profile ─────────────────────────────────────────────────────────
    Route::get('/profile',         [ProfileController::class, 'show']);
    Route::put('/profile',         [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);

    // ─── Workers (Find Labour) ──────────────────────────────────────────
    Route::get('/workers',      [WorkerController::class, 'search']);
    Route::get('/workers/{id}', [WorkerController::class, 'show']);

    // ─── Jobs ────────────────────────────────────────────────────────────
    Route::get('/jobs',         [JobController::class, 'index']);
    Route::post('/jobs',        [JobController::class, 'store']);
    Route::get('/jobs/{id}',    [JobController::class, 'show']);
    Route::put('/jobs/{id}',    [JobController::class, 'update']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);

    // ─── Job Offers ──────────────────────────────────────────────────────
    Route::get('/job-offers',        [JobOfferController::class, 'index']);
    Route::post('/job-offers',       [JobOfferController::class, 'store']);
    Route::put('/job-offers/{id}',   [JobOfferController::class, 'update']);

    // ─── Conversations & Messages ────────────────────────────────────────
    Route::get('/conversations',               [ConversationController::class, 'index']);
    Route::post('/conversations',              [ConversationController::class, 'store']);
    Route::get('/conversations/{id}',          [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);

    // ─── Notifications ───────────────────────────────────────────────────
    Route::get('/notifications',             [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read',   [NotificationController::class, 'markRead']);
    Route::put('/notifications/read-all',    [NotificationController::class, 'markAllRead']);

    // ─── Worker Profile ──────────────────────────────────────────────────
    Route::get('/worker-profile', [WorkerProfileController::class, 'show']);
    Route::put('/worker-profile', [WorkerProfileController::class, 'update']);

    // ─── Earnings ────────────────────────────────────────────────────────
    Route::get('/earnings', [EarningsController::class, 'index']);

    // ─── Settings ────────────────────────────────────────────────────────
    Route::put('/settings/password', [SettingsController::class, 'changePassword']);
});
