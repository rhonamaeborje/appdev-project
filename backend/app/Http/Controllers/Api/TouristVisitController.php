<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tourist;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TouristVisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tourist_id' => ['nullable', 'integer', 'exists:tourists,id'],
            'full_name' => ['required_without:tourist_id', 'string', 'max:255'],
            'address' => ['required_without:tourist_id', 'string', 'max:2000'],
            'gender' => ['required_without:tourist_id', 'string', 'max:30'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'purpose' => ['required', 'in:Tour,Visit/Appointment,To Borrow'],
        ]);

        if (!empty($validated['tourist_id'])) {
            $tourist = Tourist::query()
                ->where('is_archived', false)
                ->findOrFail($validated['tourist_id']);
        } else {
            $tourist = Tourist::create([
                'full_name' => $validated['full_name'],
                'address' => $validated['address'],
                'gender' => $validated['gender'],
                'contact_number' => $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'date_registered' => now('Asia/Manila')->toDateString(),
                'is_archived' => false,
            ]);
        }

        $visit = Transaction::create([
            'tourist_id' => $tourist->id,
            'date_borrowed_or_visited' => now('Asia/Manila')->toDateString(),
            'purpose' => $validated['purpose'],
            'arrival_time' => now('Asia/Manila')->format('H:i:s'),
            'status' => 'In Progress',
            'staff_id' => $request->user()?->id,
            'is_archived' => false,
        ]);

        return response()->json([
            'message' => 'Tourist checked in.',
            'data' => $visit->load('tourist'),
        ], 201);
    }

    public function depart(Request $request, Transaction $transaction): JsonResponse
    {
        if (!$transaction->tourist_id || $transaction->status !== 'In Progress') {
            return response()->json([
                'message' => 'This visit is not currently checked in.',
            ], 409);
        }

        $transaction->update([
            'departure_time' => now('Asia/Manila')->format('H:i:s'),
            'status' => 'Completed',
            'staff_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Tourist checked out.',
            'data' => $transaction->load(['tourist', 'staff']),
        ]);
    }
}