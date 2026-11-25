<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tourist;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TouristController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Tourist::query()
            ->where('is_archived', false)
            ->orderBy('full_name');

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get([
                'id',
                'full_name',
                'address',
                'gender',
                'contact_number',
                'email',
                'date_registered',
            ]),
        ]);
    }

    public function archived(): JsonResponse
    {
        $tourists = Tourist::query()
            ->where('is_archived', true)
            ->orderBy('full_name')
            ->get([
                'id',
                'full_name',
                'address',
                'gender',
                'contact_number',
                'email',
                'date_registered',
            ]);

        return response()->json(['data' => $tourists]);
    }

    public function archive(Tourist $tourist): JsonResponse
    {
        if ($tourist->is_archived) {
            return response()->json(['message' => 'Tourist is already archived.'], 409);
        }

        $hasActiveVisit = Transaction::query()
            ->where('tourist_id', $tourist->id)
            ->where('is_archived', false)
            ->where('status', 'In Progress')
            ->exists();

        if ($hasActiveVisit) {
            return response()->json([
                'message' => 'Tourist is currently checked in and cannot be archived.',
            ], 409);
        }

        $tourist->is_archived = true;
        $tourist->save();

        return response()->json([
            'message' => 'Tourist archived successfully.',
            'data' => $tourist->fresh(),
        ]);
    }

    public function restore(Tourist $tourist): JsonResponse
    {
        if (!$tourist->is_archived) {
            return response()->json(['message' => 'Tourist is not archived.'], 409);
        }

        $tourist->is_archived = false;
        $tourist->save();

        return response()->json([
            'message' => 'Tourist restored successfully.',
            'data' => $tourist->fresh(),
        ]);
    }

    public function uploadImage(Request $request, TouristSpot $touristSpot): JsonResponse
{
    $validated = $request->validate([
        'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ]);

    if ($touristSpot->image_path) {
        Storage::disk('public')->delete($touristSpot->image_path);
    }

    $path = $request->file('image')->store('tourist-spots', 'public');

    $touristSpot->update(['image_path' => $path]);

    return response()->json([
        'message' => 'Tourist spot image uploaded successfully.',
        'image_path' => $path,
        'image_url' => Storage::disk('public')->url($path),
    ]);
}
}