<?php

namespace App\Http\Controllers;

use App\Events\UnauthorizedMovementDetected;
use App\Mail\UnauthorizedMovementAlert;
use App\Models\AccessLog;
use App\Models\Alert;
use App\Models\Door;
use App\Models\MovementDetection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MovementDetectionController extends Controller
{
    // Time window in seconds to consider a movement authorized after RFID access
    protected $authorizationWindow = 30;

    /**
     * Log a movement detection from the PIR sensor
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logMovement(Request $request)
    {
        $request->validate([
            'door_id' => 'required|exists:doors,id',
            'sensor_id' => 'required|string',
        ]);

        $door = Door::findOrFail($request->door_id);

        // Check if there was a recent authorized access for this door
        $recentAuthorizedAccess = AccessLog::where('door_id', $request->door_id)
            ->where('access_type', 'authorized')
            ->where('status', 'success')
            ->where('accessed_at', '>=', now()->subSeconds($this->authorizationWindow))
            ->exists();

        // Calculate time since last authorized access
        $lastAuthorizedAccess = AccessLog::where('door_id', $request->door_id)
            ->where('access_type', 'authorized')
            ->where('status', 'success')
            ->latest('accessed_at')
            ->first();

        $unauthorizedDuration = null;
        if ($lastAuthorizedAccess) {
            $unauthorizedDuration = now()->diffInSeconds($lastAuthorizedAccess->accessed_at);
        }

        // Create movement detection record
        $movement = MovementDetection::create([
            'door_id' => $request->door_id,
            'has_recent_authorization' => $recentAuthorizedAccess,
            'unauthorized_duration' => $unauthorizedDuration,
            'detected_at' => now(),
        ]);

        // If no recent authorization, this is a potential security breach
        if (!$recentAuthorizedAccess) {
            // Create an alert
            $alert = Alert::create([
                'door_id' => $request->door_id,
                'movement_detection_id' => $movement->id,
                'alert_type' => 'unauthorized_movement',
                'description' => "Unauthorized movement detected at door: {$door->name} ({$door->location})",
                'triggered_at' => now(),
            ]);

            // Trigger the buzzer (this would actually be handled by the IoT device)
            // But we can send an event that the frontend can listen to
            event(new UnauthorizedMovementDetected($alert));

            // Send email notification to admin users
            $this->sendAlertNotifications($alert);

            // Return with alert info
            return response()->json([
                'movement_logged' => true,
                'movement_id' => $movement->id,
                'requires_action' => true,
                'alert_created' => true,
                'alert_id' => $alert->id,
                'message' => 'Unauthorized movement detected! Alert has been created.'
            ], 201);
        }

        // Return confirmation for authorized movement
        return response()->json([
            'movement_logged' => true,
            'movement_id' => $movement->id,
            'requires_action' => false,
            'message' => 'Movement logged with recent authorization.'
        ]);
    }

    /**
     * Send alert notifications to administrators
     *
     * @param Alert $alert
     * @return void
     */
    protected function sendAlertNotifications(Alert $alert)
    {
        // Get all admin users who should receive alerts
        $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($adminUsers as $admin) {
            // Queue the email to be sent
            Mail::to($admin->email)->queue(new UnauthorizedMovementAlert($alert));
        }
    }

    /**
     * Get list of movement detections with optional filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = MovementDetection::with(['door:id,name,location', 'alert']);

        // Apply filters
        if ($request->has('door_id')) {
            $query->where('door_id', $request->door_id);
        }

        if ($request->has('has_recent_authorization')) {
            $query->where('has_recent_authorization', $request->has_recent_authorization === 'true');
        }

        if ($request->has('start_date')) {
            $query->whereDate('detected_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('detected_at', '<=', $request->end_date);
        }

        $movements = $query->orderBy('detected_at', 'desc')->paginate(15);

        return response()->json($movements);
    }

    /**
     * Get movement statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        // Initialize query
        $query = MovementDetection::query();

        // Apply date filters if provided
        if ($request->has('start_date')) {
            $query->whereDate('detected_at', '>=', $request->start_date);
        } else {
            // Default to last 30 days
            $query->whereDate('detected_at', '>=', now()->subDays(30));
        }

        if ($request->has('end_date')) {
            $query->whereDate('detected_at', '<=', $request->end_date);
        }

        // Basic statistics
        $totalMovements = $query->count();
        $authorizedMovements = (clone $query)->where('has_recent_authorization', true)->count();
        $unauthorizedMovements = (clone $query)->where('has_recent_authorization', false)->count();

        // Daily stats for the chart
        $dailyStats = (clone $query)
            ->select(
                DB::raw('DATE(detected_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN has_recent_authorization = 1 THEN 1 ELSE 0 END) as authorized'),
                DB::raw('SUM(CASE WHEN has_recent_authorization = 0 THEN 1 ELSE 0 END) as unauthorized')
            )
            ->groupBy(DB::raw('DATE(detected_at)'))
            ->orderBy('date')
            ->get();

        // Door stats
        $doorStats = (clone $query)
            ->select(
                'door_id',
                DB::raw('COUNT(*) as movement_count'),
                DB::raw('SUM(CASE WHEN has_recent_authorization = 1 THEN 1 ELSE 0 END) as authorized'),
                DB::raw('SUM(CASE WHEN has_recent_authorization = 0 THEN 1 ELSE 0 END) as unauthorized')
            )
            ->groupBy('door_id')
            ->with('door:id,name,location')
            ->orderByDesc('movement_count')
            ->take(5)
            ->get();

        // Time of day analysis (hourly distribution)
        $hourlyDistribution = (clone $query)
            ->select(DB::raw('HOUR(detected_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('HOUR(detected_at)'))
            ->orderBy('hour')
            ->get();

        // Response data
        $statistics = [
            'total_movements' => $totalMovements,
            'authorized_movements' => $authorizedMovements,
            'unauthorized_movements' => $unauthorizedMovements,
            'authorization_rate' => $totalMovements > 0 ? round(($authorizedMovements / $totalMovements) * 100, 2) : 0,
            'daily_stats' => $dailyStats,
            'door_stats' => $doorStats,
            'hourly_distribution' => $hourlyDistribution
        ];

        return response()->json($statistics);
    }
}
