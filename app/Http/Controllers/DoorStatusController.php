<?php

namespace App\Http\Controllers;

use App\Models\Door;
use Illuminate\Http\Request;

class DoorStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doors = Door::with('currentStatus')->get();

        $doorStatuses = $doors->map(function ($door) {
            return [
                'id' => $door->id,
                'name' => $door->name,
                'location' => $door->location,
                'status' => $door->currentStatus ? $door->currentStatus->status : 'unknown',
                'last_updated' => $door->currentStatus ? $door->currentStatus->status_changed_at : null,
                'is_active' => $door->is_active
            ];
        });

        return response()->json([
            'door_statuses' => $doorStatuses
        ]);
    }
}
