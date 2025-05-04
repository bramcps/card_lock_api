<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\RfidCard;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AccessLog::with(['user:id,name,email', 'door:id,name,location', 'rfidCard:id,card_number,card_name']);

        // Apply filters if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('door_id')) {
            $query->where('door_id', $request->door_id);
        }

        if ($request->has('rfid_card_id')) {
            $query->where('rfid_card_id', $request->rfid_card_id);
        }

        if ($request->has('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('accessed_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('accessed_at', '<=', $request->end_date);
        }

        // Regular users can only see their own logs
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $logs = $query->orderBy('accessed_at', 'desc')->paginate(15);

        return response()->json($logs);
    }

    public function logAccess(Request $request)
    {
        // This endpoint needs to be authenticated with an API token
        // Validate basic request data
        $request->validate([
            'card_number' => 'required|string',
            'door_id' => 'required|exists:doors,id',
        ]);

        // Look up card
        $rfidCard = RfidCard::where('card_number', $request->card_number)->first();

        // If card not found
        if (!$rfidCard) {
            // Log unknown access attempt
            $log = AccessLog::create([
                'user_id' => null,
                'rfid_card_id' => null,
                'door_id' => $request->door_id,
                'access_type' => 'unauthorized',
                'status' => 'failed',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'access_granted' => false,
                'reason' => 'unknown_card',
                'log_id' => $log->id
            ]);
        }

        // Check if card is active and not expired
        if (!$rfidCard->isValid()) {
            // Log access attempt with invalid card
            $log = AccessLog::create([
                'user_id' => $rfidCard->user_id,
                'rfid_card_id' => $rfidCard->id,
                'door_id' => $request->door_id,
                'access_type' => 'unauthorized',
                'status' => 'failed',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'access_granted' => false,
                'reason' => $rfidCard->isExpired() ? 'expired_card' : 'inactive_card',
                'log_id' => $log->id
            ]);
        }

        // Check if user exists and is active
        $user = $rfidCard->user;
        if (!$user || !$user->is_active) {
            // Log access attempt with invalid user
            $log = AccessLog::create([
                'user_id' => $rfidCard->user_id,
                'rfid_card_id' => $rfidCard->id,
                'door_id' => $request->door_id,
                'access_type' => 'unauthorized',
                'status' => 'failed',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'access_granted' => false,
                'reason' => 'inactive_user',
                'log_id' => $log->id
            ]);
        }

        // Check if user has permission for this door
        $permission = UserPermission::where('user_id', $user->id)
            ->where('door_id', $request->door_id)
            ->where('is_active', true)
            ->first();

        if (!$permission) {
            // Log unauthorized access attempt
            $log = AccessLog::create([
                'user_id' => $user->id,
                'rfid_card_id' => $rfidCard->id,
                'door_id' => $request->door_id,
                'access_type' => 'unauthorized',
                'status' => 'failed',
                'accessed_at' => now(),
            ]);

            return response()->json([
                'access_granted' => false,
                'reason' => 'no_permission',
                'log_id' => $log->id
            ]);
        }

        // Check time restrictions if they exist
        if ($permission->access_start_time || $permission->access_end_time) {
            if (!$permission->hasPermissionNow()) {
                // Log access attempt outside permitted hours
                $log = AccessLog::create([
                    'user_id' => $user->id,
                    'rfid_card_id' => $rfidCard->id,
                    'door_id' => $request->door_id,
                    'access_type' => 'unauthorized',
                    'status' => 'failed',
                    'accessed_at' => now(),
                ]);

                return response()->json([
                    'access_granted' => false,
                    'reason' => 'outside_hours',
                    'log_id' => $log->id
                ]);
            }
        }

        // All checks passed, grant access
        $log = AccessLog::create([
            'user_id' => $user->id,
            'rfid_card_id' => $rfidCard->id,
            'door_id' => $request->door_id,
            'access_type' => 'authorized',
            'status' => 'success',
            'accessed_at' => now(),
        ]);

        return response()->json([
            'access_granted' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ],
            'log_id' => $log->id
        ]);
    }

    public function statistics(Request $request)
    {
        $filter = $request->get('filter', 'Hari'); // default: Hari

        $logs = AccessLog::where('status', 'Granted')
            ->selectRaw('accessed_at')
            ->get();

        $grouped = [];

        foreach ($logs as $log) {
            $date = $log->accessed_at;

            if ($filter === 'Hari') {
                $key = $date->format('Y-m-d');
            } elseif ($filter === 'Minggu') {
                $key = $date->startOfWeek()->format('Y-m-d');
            } elseif ($filter === 'Bulan') {
                $key = $date->format('Y-m');
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = 0;
            }

            $grouped[$key]++;
        }

        $result = [];

        foreach ($grouped as $key => $total) {
            if ($filter === 'Hari') {
                $label = $key;
            } elseif ($filter === 'Minggu') {
                $label = 'Minggu ' . \Carbon\Carbon::parse($key)->format('d M');
            } elseif ($filter === 'Bulan') {
                $label = \Carbon\Carbon::parse($key . '-01')->format('M Y');
            }

            $result[] = [
                'date' => $label,
                'total' => $total,
            ];
        }

        return response()->json($result);
    }
}
