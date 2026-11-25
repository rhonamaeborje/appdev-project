<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Item::query()
            ->where('is_archived', false)
            ->orderBy('item_name')
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255', 'unique:items,item_name'],
            'category' => ['required', Rule::in(['Equipment', 'Facility'])],
            'condition_before' => ['nullable', 'string', 'max:255'],
            'condition_after' => ['nullable', 'string', 'max:255'],
        ]);

        $item = Item::create($validated);

        return response()->json([
            'message' => 'Item created successfully.',
            'data' => $item,
        ], 201);
    }

    public function archived(): JsonResponse
    {
        $items = Item::query()
            ->where('is_archived', true)
            ->orderBy('item_name')
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function show(Item $item): JsonResponse
    {
        if ($item->is_archived) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json([
            'data' => $item,
        ]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        if ($item->is_archived) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        $validated = $request->validate([
            'item_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('items', 'item_name')->ignore($item->id),
            ],
            'category' => [
                'sometimes',
                'required',
                Rule::in(['Equipment', 'Facility']),
            ],
            'condition_before' => ['sometimes', 'nullable', 'string', 'max:255'],
            'condition_after' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully.',
            'data' => $item->fresh(),
        ]);
    }

    public function archive(Item $item): JsonResponse
    {
        if ($item->is_archived) {
            return response()->json([
                'message' => 'Item is already archived.',
            ], 409);
        }

        $hasOpenTransaction = Transaction::query()
            ->where('is_archived', false)
            ->whereIn('status', ['Pending', 'Approved', 'Borrowed', 'Overdue'])
            ->whereHas('items', function ($query) use ($item) {
                $query->where('items.id', $item->id);
            })
            ->exists();

        if ($hasOpenTransaction) {
            return response()->json([
                'message' => 'This item is linked to a pending or active borrowing and cannot be archived yet.',
            ], 409);
        }

        $item->update([
            'is_archived' => true,
        ]);

        return response()->json([
            'message' => 'Item archived successfully.',
            'data' => $item->fresh(),
        ]);
    }

    public function restore(Item $item): JsonResponse
    {
        if (!$item->is_archived) {
            return response()->json([
                'message' => 'Item is not archived.',
            ], 409);
        }

        $item->update([
            'is_archived' => false,
        ]);

        return response()->json([
            'message' => 'Item restored successfully.',
            'data' => $item->fresh(),
        ]);
    }
}