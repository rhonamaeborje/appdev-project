<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\TouristSpot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TouristSpotController extends Controller
{
    public function index(): JsonResponse
    {
        $spots = TouristSpot::query()
            ->where('is_archived', false)
            ->orderBy('spot_name')
            ->get();

        return response()->json(['data' => $spots]);
    }

    public function archived(): JsonResponse
    {
        $spots = TouristSpot::query()
            ->where('is_archived', true)
            ->orderBy('spot_name')
            ->get();

        return response()->json(['data' => $spots]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'spot_name' => ['required', 'string', 'max:255', 'unique:tourist_spots,spot_name'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
        ]);

        $spot = TouristSpot::create($validated);

        return response()->json([
            'message' => 'Tourist spot created successfully.',
            'data' => $spot,
        ], 201);
    }

    public function show(TouristSpot $touristSpot): JsonResponse
    {
        if ($touristSpot->is_archived) {
            return response()->json(['message' => 'Tourist spot not found.'], 404);
        }

        return response()->json(['data' => $touristSpot]);
    }

    public function update(Request $request, TouristSpot $touristSpot): JsonResponse
    {
        if ($touristSpot->is_archived) {
            return response()->json(['message' => 'Tourist spot not found.'], 404);
        }

        $validated = $request->validate([
            'spot_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tourist_spots', 'spot_name')->ignore($touristSpot->id),
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $touristSpot->update($validated);

        return response()->json([
            'message' => 'Tourist spot updated successfully.',
            'data' => $touristSpot->fresh(),
        ]);
    }

    public function archive(TouristSpot $touristSpot): JsonResponse
    {
        if ($touristSpot->is_archived) {
            return response()->json(['message' => 'Tourist spot is already archived.'], 409);
        }

        $touristSpot->update(['is_archived' => true]);

        return response()->json([
            'message' => 'Tourist spot archived successfully.',
            'data' => $touristSpot->fresh(),
        ]);
    }

    public function restore(TouristSpot $touristSpot): JsonResponse
    {
        if (!$touristSpot->is_archived) {
            return response()->json(['message' => 'Tourist spot is not archived.'], 409);
        }

        $touristSpot->update(['is_archived' => false]);

        return response()->json([
            'message' => 'Tourist spot restored successfully.',
            'data' => $touristSpot->fresh(),
        ]);
    }

    public function uploadImage(Request $request, TouristSpot $touristSpot): JsonResponse
{
    $request->validate([
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