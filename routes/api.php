<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ActualsController;
use App\Http\Controllers\Api\DocumentationController; 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // ── Budget Periods ──
    Route::get('/periods', [BudgetController::class, 'periods']);

    // ── Budgets ──
    Route::get('/budgets',                          [BudgetController::class, 'index']);
    Route::get('/budgets/{budget}',                 [BudgetController::class, 'show']);
    Route::patch('/budgets/{budget}/line-items',    [BudgetController::class, 'updateLineItems'])->middleware('permission:create budget');
    Route::post('/budgets/{budget}/submit',         [BudgetController::class, 'submit'])->middleware('permission:create budget');

    // ── Departments ──
    Route::get('/departments',              [DepartmentController::class, 'index']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);

    // ── Actuals ──
    Route::get('/actuals',  [ActualsController::class, 'index']);
    Route::post('/actuals', [ActualsController::class, 'store']);

    // ── Reports ──
    Route::get('/reports/summary',      [ReportController::class, 'summary']);
    Route::get('/reports/departments',  [ReportController::class, 'departments']);
    Route::get('/reports/variance',     [ReportController::class, 'variance']);

    // ── Documentation ──
    Route::get('/', [DocumentationController::class, 'index']);
});
