<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::paginate(15);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|max:16',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => "{$user->email} berhasil dibuat",
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        // Include user's RFID cards
        $user->load('rfidCards');

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|string|email|max:255|unique:users,email,$id",
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,user',
            'is_active' => 'sometimes|boolean',
        ]);

        $userData = $request->only(['name', 'email', 'role', 'is_active']);

        // Only update password if provided
        if ($request->has('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return response()->json([
            'message' => "{$user->email} berhasil diperbarui",
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak bisa menghapus akun anda sendiri'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => "{$user->email} berhasil dihapus",
        ]);
    }

    public function deactivate(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Don't allow deactivating yourself
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak bisa menonaktifkan akun anda sendiri'
            ], 403);
        }

        $user->update(['is_active' => false]);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => "{$user->email} berhasil dinonaktifkan"
        ]);
    }

    public function activate(string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true]);

        return response()->json([
            'message' => "{$user->email} berhasil diaktifkan"
        ]);
    }
}
