<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Loaded under the "web" group with an /api prefix: the SPA uses session cookies + CSRF.
// AI clients never hit these routes — they use the MCP endpoint in routes/ai.php.

Route::middleware(['guest', 'throttle:auth'])->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::middleware(['guest', 'throttle:password-reset'])->group(function () {
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:web')->group(function () {
    Route::get('auth/me', [AuthController::class, 'show']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('account', [AccountController::class, 'show']);
    Route::patch('account', [AccountController::class, 'updateProfile']);
    Route::put('account/password', [AccountController::class, 'changePassword'])->middleware('throttle:6,1');
    Route::put('account/email-preferences', [AccountController::class, 'updatePreferences']);

    Route::get('dashboard', DashboardController::class);

    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);

    Route::middleware('project.member')->prefix('projects/{project}')->group(function () {
        Route::get('/', [ProjectController::class, 'show']);
        Route::patch('/', [ProjectController::class, 'update']);
        Route::delete('/', [ProjectController::class, 'destroy']);
        Route::post('members', [ProjectMemberController::class, 'store']);
        Route::delete('members/{user}', [ProjectMemberController::class, 'destroy'])->whereNumber('user');
        Route::get('attachments', [AttachmentController::class, 'indexForProject']);
        Route::post('attachments', [AttachmentController::class, 'storeForProject'])->middleware('throttle:uploads');
    });

    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
    Route::prefix('tasks/{task}')->group(function () {
        Route::get('/', [TaskController::class, 'show']);
        Route::patch('/', [TaskController::class, 'update']);
        Route::delete('/', [TaskController::class, 'destroy']);
        Route::post('status', [TaskController::class, 'status']);
        Route::post('review-request', [TaskController::class, 'requestReview']);
        Route::post('review', [TaskController::class, 'review']);
        Route::post('comments', [TaskController::class, 'comment']);
        Route::get('attachments', [AttachmentController::class, 'indexForTask']);
        Route::post('attachments', [AttachmentController::class, 'storeForTask'])->middleware('throttle:uploads');
    });

    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->whereNumber('attachment');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->whereNumber('attachment');

    Route::get('connections', [ConnectionController::class, 'index']);
    Route::post('connections', [ConnectionController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('connections/{connection}', [ConnectionController::class, 'destroy']);
});
