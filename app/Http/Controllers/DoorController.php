<?php

namespace App\Http\Controllers;

use App\Models\Door;
use App\Models\DoorStatus;
use Illuminate\Http\Request;

class DoorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $doors = Door::with('currentStatus')->paginate(15);
            return response()->json($doors);
        }

        // For regular users, only get doors they have access to
        $doors = $request->user()->accessibleDoors()->with('currentStatus')->get();
        return response()->json($doors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $door = Door::create([
            'name' => $request->name,
            'location' => $request->location,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        // Create initial door status
        DoorStatus::create([
            'door_id' => $door->id,
            'status' => 'closed',
            'status_changed_at' => now(),
            'changed_by' => $request->user()->id,
            'change_method' => 'manual',
        ]);

        return response()->json([
            'message' => 'Pintu berhasil dibuat',
            'door' => $door
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $door = Door::with('currentStatus')->findOrFail($id);

        // Check if user has permission to view this door
        if (!$request->user()->isAdmin() &&
            !$request->user()->accessibleDoors()->where('doors.id', $door->id)->exists()) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk melihat pintu ini'
            ], 403);
        }

        return response()->json($door);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $door = Door::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $door->update($request->only([
            'name', 'location', 'description', 'is_active'
        ]));

        return response()->json([
            'message' => 'Pintu berhasil diperbarui',
            'door' => $door
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $door = Door::findOrFail($id);

        // First check if there are any access logs or alerts
        if ($door->accessLogs()->count() > 0 || $door->alerts()->count() > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus pintu dengan log akses atau peringatan yang ada. Pertimbangkan untuk menonaktifkannya.'
            ], 422);
        }

        // Delete door statuses
        $door->statuses()->delete();

        // Delete the door
        $door->delete();

        return response()->json([
            'message' => 'Pintu berhasil dihapus'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $door = Door::findOrFail($id);

        $request->validate([
            'status' => 'required|in:open,closed,locked,unlocked',
        ]);

        // Create new door status
        DoorStatus::create([
            'door_id' => $door->id,
            'status' => $request->status,
            'status_changed_at' => now(),
            'changed_by' => $request->user()->id,
            'change_method' => 'manual',
        ]);

        return response()->json([
            'message' => 'Status pintu berhasil diperbarui',
            'status' => $request->status
        ]);
    }

    public function history(Request $request, $id)
    {
        $door = Door::findOrFail($id);

        // Optional date filtering
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = $door->statuses();

        if ($startDate) {
            $query->whereDate('status_changed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('status_changed_at', '<=', $endDate);
        }

        $history = $query->with('changedByUser:id,name')->orderBy('status_changed_at', 'desc')->paginate(15);

        return response()->json($history);
    }
}
