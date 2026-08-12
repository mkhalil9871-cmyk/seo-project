<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProjectController;

Route::middleware('throttle:6,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::apiResource('projects', ProjectController::class);

    Route::post('/projects/{project}/audits', [AuditController::class, 'start']);
    Route::get('/audits/{audit}', [AuditController::class, 'status']);
    Route::get('/audits/{audit}/issues', [AuditController::class, 'issues']);
    Route::get('/audits/{audit}/issues/summary', [AuditController::class, 'issuesSummary']);
    Route::get('/audits/{audit}/export.csv', [AuditController::class, 'exportCsv']);
    Route::get('/audits/{audit}/export-pages.csv', [AuditController::class, 'exportPagesCsv']);
});