<?php

use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

// API v1 — Token-authenticated endpoints
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api', 'token.project'])
    ->group(function () {
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::get('/projects/{project:slug}', [ProjectController::class, 'show']);
        Route::get('/projects/{project:slug}/units', [ProjectController::class, 'units']);
        Route::get('/projects/{project:slug}/units/{unit}', [ProjectController::class, 'unit']);
        Route::get('/projects/{project:slug}/availability', [ProjectController::class, 'availability']);
    });
