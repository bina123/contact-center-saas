<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TenantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhooks/call', [CallController::class, 'store']); // Telephony webhook

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles', 'tenant');
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/overview', [DashboardController::class, 'overview']);
        Route::get('/call-volume', [DashboardController::class, 'callVolumeChart']);
        Route::get('/agent-performance', [DashboardController::class, 'agentPerformance']);
        Route::get('/call-status-distribution', [DashboardController::class, 'callStatusDistribution']);
        Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
    });

    // Calls
    Route::prefix('calls')->group(function () {
        Route::get('/', [CallController::class, 'index']);
        Route::get('/active', [CallController::class, 'active']);
        Route::get('/my-calls', [CallController::class, 'myCalls']);
        Route::get('/statistics', [CallController::class, 'statistics']);
        Route::get('/{call}', [CallController::class, 'show']);
        Route::post('/{call}/answer', [CallController::class, 'answer']);
        Route::post('/{call}/end', [CallController::class, 'end']);
        Route::patch('/{call}/notes', [CallController::class, 'updateNotes']);
    });

    // Tickets
    Route::apiResource('tickets', TicketController::class);
    Route::post('/tickets/{ticket}/messages', [TicketController::class, 'addMessage']);
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);

    // Campaigns
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/start', [CampaignController::class, 'start']);
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause']);
    Route::post('/campaigns/{campaign}/leads', [CampaignController::class, 'addLeads']);

    // Users/Agents
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::patch('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::patch('/{user}/status', [UserController::class, 'updateStatus']);
        Route::get('/{user}/statistics', [UserController::class, 'statistics']);
    });

    // Tenant management (Admin only)
    Route::middleware('role:admin')->prefix('tenant')->group(function () {
        Route::get('/', [TenantController::class, 'show']);
        Route::patch('/', [TenantController::class, 'update']);
        Route::get('/statistics', [TenantController::class, 'statistics']);
        Route::post('/subscription', [TenantController::class, 'updateSubscription']);
    });
});
