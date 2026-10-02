<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'me']);
        Route::get('dashboard', [DashboardController::class, 'show']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('task-managers', [ProjectController::class, 'taskManagers']);
        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('projects.tasks', TaskController::class)->except(['show']);
        Route::get('projects/{project}/tasks/{task}/activity', [ActivityLogController::class, 'index']);
    });
});
