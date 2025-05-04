<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $query = Alert::with(['door:id,name,location', 'movementDetection', 'acknowledgedByUser:id,name']);

        // Apply filters if provided
        if ($request->has('door_id')) {
            $query->where('door_id', $request->door_id);
        }

        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->has('is_acknowledged')) {
            $query->where('is_acknowledged', $request->is_acknowledged === 'true');
        }

        if ($request->has('start_date')) {
            $query->whereDate('triggered_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('triggered_at', '<=', $request->end_date);
        }

        $alerts = $query->orderBy('triggered_at', 'desc')->paginate(15);

        return response()->json($alerts);
    }

    /**
     * Display a listing of unacknowledged alerts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unacknowledged()
    {
        $alerts = Alert::with(['door:id,name,location', 'movementDetection'])
            ->where('is_acknowledged', false)
            ->orderBy('triggered_at', 'desc')
            ->get();

        return response()->json($alerts);
    }

    /**
     * Acknowledge an alert
     *
     * @param Request $request
     * @param Alert $alert
     * @return \Illuminate\Http\JsonResponse
     */
    public function acknowledge(Request $request, Alert $alert)
    {
        if ($alert->is_acknowledged) {
            return response()->json([
                'message' => 'Alert is already acknowledged'
            ], 422);
        }

        $alert->acknowledge($request->user()->id);

        return response()->json([
            'message' => 'Alert acknowledged successfully',
            'alert' => $alert->fresh(['acknowledgedByUser:id,name'])
        ]);
    }
}
