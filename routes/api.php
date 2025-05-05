<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\MovementDetectionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoorController;
use App\Http\Controllers\DoorStatusController;
use App\Http\Controllers\RfidCardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\AlertController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $appName = config('app.name');

    return response()->json([
        'success' => true,
        'message' => "Welcome to $appName"
    ]);
})->name('home');

Route::post('/signin', [AuthController::class, 'signIn']);

// Protected routes - require user authentication
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/signout', [AuthController::class, 'signOut']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Door status - available to all authenticated users
    Route::get('/door_status', [DoorStatusController::class, 'index']);

    // Door routes - with permissions
    Route::get('/doors', [DoorController::class, 'index']);
    Route::get('/doors/{id}', [DoorController::class, 'show']);

    // Door status history
    Route::get('/doors/{id}/history', [DoorController::class, 'history']);

    // User can see their own RFID cards
    Route::get('/rfid_cards', [RfidCardController::class, 'index']);
    Route::get('/rfid_cards/{id}', [RfidCardController::class, 'show']);

    // Access logs - users can see their own logs
    Route::get('/access_logs', [AccessLogController::class, 'index']);
    Route::get('/access_logs/statistics', [AccessLogController::class, 'statistics']);

    // Alert routes - users can see alerts for doors they have access to
    Route::get('/alerts', [AlertController::class, 'index']);

    // Movement detection
    Route::post('/movement', [MovementDetectionController::class, 'logMovement']);

    // Get door status for hardware
    Route::get('/door/{id}/status', [DoorStatusController::class, 'show']);

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        // User management
        Route::apiResource('users', UserController::class);
        Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/users/{id}/activate', [UserController::class, 'activate']);

        // Door management
        Route::post('/doors', [DoorController::class, 'store']);
        Route::put('/doors/{id}', [DoorController::class, 'update']);
        Route::delete('/doors/{id}', [DoorController::class, 'destroy']);
        Route::post('/doors/{id}/status', [DoorController::class, 'updateStatus']);

        // RFID card management
        Route::post('/rfid_cards', [RfidCardController::class, 'store']);
        Route::put('/rfid_cards/{id}', [RfidCardController::class, 'update']);
        Route::delete('/rfid_cards/{id}', [RfidCardController::class, 'destroy']);

        // User permission management
        Route::get('/permissions', [UserPermissionController::class, 'index']);
        Route::post('/permissions', [UserPermissionController::class, 'store']);
        Route::get('/permissions/{id}', [UserPermissionController::class, 'show']);
        Route::put('/permissions/{id}', [UserPermissionController::class, 'update']);
        Route::delete('/permissions/{id}', [UserPermissionController::class, 'destroy']);

        // RFID card access attempt
        Route::post('/access_logs', [AccessLogController::class, 'logAccess']);

        // Alert management
        Route::put('/alerts/{id}/unacknowledged', [AlertController::class, 'unacknowledged']);
        Route::put('/alerts/{id}/acknowledge', [AlertController::class, 'acknowledge']);

        // Movement detection
        Route::get('/movement_detections', [MovementDetectionController::class, 'index']);
        Route::get('/movement_detections/statistics', [MovementDetectionController::class, 'statistics']);
    });
});
