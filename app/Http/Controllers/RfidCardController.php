<?php

namespace App\Http\Controllers;

use App\Models\RfidCard;
use Illuminate\Http\Request;

class RfidCardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->isAdmin() && $request->has('user_id')) {
            $cards = RfidCard::where('user_id', $request->user_id)->get();
        }
        // If admin requesting all cards
        elseif ($request->user()->isAdmin()) {
            $cards = RfidCard::with('user')->paginate(15);
        }
        // Normal users can only see their own cards
        else {
            $cards = $request->user()->rfidCards;
        }

        return response()->json([
            'cards' => $cards
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string|unique:rfid_cards',
            'card_name' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:today',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        // Determine the user_id based on role
        $userId = $request->user()->isAdmin() && $request->has('user_id')
            ? $request->user_id
            : $request->user()->id;

        $card = RfidCard::create([
            'user_id' => $userId,
            'card_number' => $request->card_number,
            'card_name' => $request->card_name,
            'is_active' => true,
            'issued_at' => now(),
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'message' => 'Kartu RFID berhasil dibuat',
            'card' => $card
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $card = RfidCard::findOrFail($id);

        // Check if the card belongs to the authenticated user or user is admin
        if ($card->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk melihat kartu ini'
            ], 403);
        }

        return response()->json([
            'card' => $card
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $card = RfidCard::findOrFail($id);

        // Check if the card belongs to the authenticated user or user is admin
        if ($card->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk mengubah kartu ini'
            ], 403);
        }

        $request->validate([
            'card_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        // Only admin can change the user assignment
        $updateData = $request->only([
            'card_name',
            'is_active',
            'expires_at',
        ]);

        if ($request->user()->isAdmin() && $request->has('user_id')) {
            $updateData['user_id'] = $request->user_id;
        }

        $card->update($updateData);

        return response()->json([
            'message' => 'Kartu RFID berhasil diperbarui',
            'card' => $card
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $card = RfidCard::findOrFail($id);

        // Check if the card belongs to the authenticated user or user is admin
        if ($card->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menghapus kartu ini'
            ], 403);
        }

        $card->delete();

        return response()->json([
            'message' => 'Kartu RFID berhasil menghapus'
        ]);
    }
}
