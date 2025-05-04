<?php

namespace App\Http\Controllers;

use App\Models\Door;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Add filters
        $userId = $request->get('user_id');
        $doorId = $request->get('door_id');

        $query = UserPermission::with(['user:id,name,email', 'door:id,name,location']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($doorId) {
            $query->where('door_id', $doorId);
        }

        $permissions = $query->paginate(15);

        return response()->json($permissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'door_id' => 'required|exists:doors,id',
            'access_start_time' => 'nullable|date_format:H:i',
            'access_end_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        // Check if permission already exists
        $permission = UserPermission::where('user_id', $request->user_id)
            ->where('door_id', $request->door_id)
            ->first();

        if ($permission) {
            return response()->json([
                'message' => 'Perizinan sudah ada',
                'permission' => $permission
            ], 409);
        }

        // Create new permission
        $permission = UserPermission::create([
            'user_id' => $request->user_id,
            'door_id' => $request->door_id,
            'access_start_time' => $request->access_start_time,
            'access_end_time' => $request->access_end_time,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        return response()->json([
            'message' => 'Perizinan berhasil diberikan',
            'permission' => $permission
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $permission = UserPermission::findOrFail($id);

        $request->validate([
            'access_start_time' => 'nullable|date_format:H:i',
            'access_end_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $permission->update([
            'access_start_time' => $request->access_start_time,
            'access_end_time' => $request->access_end_time,
            'is_active' => $request->has('is_active') ? $request->is_active : $permission->is_active,
        ]);

        return response()->json([
            'message' => 'Perizinan berhasil diperbarui',
            'permission' => $permission
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permission = UserPermission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'message' => 'Perizinan berhasil dicabut'
        ]);
    }

    public function doorUsers($doorId)
    {
        $door = Door::findOrFail($doorId);
        $users = $door->authorizedUsers()->get(['users.id', 'name', 'email', 'user_permissions.access_start_time', 'user_permissions.access_end_time']);

        return response()->json([
            'door' => [
                'id' => $door->id,
                'name' => $door->name
            ],
            'authorized_users' => $users
        ]);
    }

    public function userDoors($userId)
    {
        $user = User::findOrFail($userId);
        $doors = $user->accessibleDoors()->get(['doors.id', 'name', 'location', 'description', 'user_permissions.access_start_time', 'user_permissions.access_end_time']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ],
            'accessible_doors' => $doors
        ]);
    }
}
