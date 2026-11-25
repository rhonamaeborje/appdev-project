<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    public function index(): JsonResponse
    {
        $transactions = Transaction::query()
            ->with(['borrower', 'tourist', 'staff', 'items'])
            ->where('is_archived', false)
            ->latest()
            ->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'borrower_id' => ['nullable', 'integer', 'exists:borrowers,id'],
            'full_name' => ['required_without:borrower_id', 'string', 'max:255'],
            'address' => ['required_without:borrower_id', 'string', 'max:2000'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_presented' => ['required_without:borrower_id', 'boolean'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'distinct', 'exists:items,id'],
            'date_borrowed_or_visited' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:date_borrowed_or_visited'],
        ]);

        return DB::transaction(function () use ($validated): JsonResponse {
            $items = Item::query()
                ->whereIn('id', $validated['item_ids'])
                ->lockForUpdate()
                ->get();

            if (
                $items->count() !== count($validated['item_ids'])
                || $items->contains(fn (Item $item) => $item->is_archived)
            ) {
                return response()->json([
                    'message' => 'One or more selected items are unavailable.',
                ], 422);
            }

            $hasDateConflict = Transaction::query()
                ->whereIn('status', ['Pending', 'Approved', 'Borrowed', 'Overdue'])
                ->where('is_archived', false)
                ->whereDate('date_borrowed_or_visited', '<=', $validated['return_date'])
                ->where(function ($query) use ($validated) {
                    $query->whereNull('return_date')
                        ->orWhereDate(
                            'return_date',
                            '>=',
                            $validated['date_borrowed_or_visited']
                        );
                })
                ->whereHas('items', function ($query) use ($validated) {
                    $query->whereIn('items.id', $validated['item_ids']);
                })
                ->exists();

            if ($hasDateConflict) {
                return response()->json([
                    'message' => 'An item is already requested or borrowed during those dates.',
                ], 422);
            }

            if (!empty($validated['borrower_id'])) {
                $borrower = Borrower::query()
                    ->where('is_archived', false)
                    ->findOrFail($validated['borrower_id']);

                if (array_key_exists('id_presented', $validated)) {
                    $borrower->update([
                        'id_presented' => $validated['id_presented'],
                    ]);
                }
            } else {
                $borrower = Borrower::create([
                    'full_name' => $validated['full_name'],
                    'address' => $validated['address'],
                    'contact_number' => $validated['contact_number'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'id_presented' => $validated['id_presented'],
                    'date_registered' => now()->toDateString(),
                ]);
            }

            $transaction = Transaction::create([
                'borrower_id' => $borrower->id,
                'date_borrowed_or_visited' => $validated['date_borrowed_or_visited'],
                'return_date' => $validated['return_date'],
                'purpose' => 'To Borrow',
                'status' => 'Pending',
                'is_archived' => false,
            ]);

            $transaction->items()->attach($items->modelKeys());

            return response()->json([
                'message' => 'Borrowing request submitted.',
                'data' => $transaction->load(['borrower', 'items']),
            ], 201);
        });
    }

    public function approve(Request $request, Transaction $transaction): JsonResponse
    {
        return DB::transaction(function () use ($request, $transaction): JsonResponse {
            $transaction = Transaction::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($transaction->status !== 'Pending') {
                return response()->json([
                    'message' => 'Only pending requests can be approved.',
                ], 409);
            }

            $items = Item::query()
                ->whereIn('id', $transaction->items->modelKeys())
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty() || $items->contains(
                fn (Item $item) => $item->is_archived
                    || $item->availability_status !== 'Available'
            )) {
                return response()->json([
                    'message' => 'One or more items are no longer available.',
                ], 409);
            }

            $transaction->update([
                'status' => 'Borrowed',
                'staff_id' => $request->user()->id,
            ]);

            foreach ($items as $item) {
                $item->update(['availability_status' => 'Not Available']);
            }

            return response()->json([
                'message' => 'Borrowing request approved.',
                'data' => $transaction->load(['borrower', 'items', 'staff']),
            ]);
        });
    }

        public function reject(Request $request, Transaction $transaction): JsonResponse
    {
        return DB::transaction(function () use ($request, $transaction): JsonResponse {
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if (!$transaction->borrower_id || $transaction->status !== 'Pending') {
                return response()->json([
                    'message' => 'Only pending borrowing requests can be rejected.',
                ], 409);
            }

            $transaction->update([
                'status' => 'Rejected',
                'staff_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Borrowing request rejected.',
                'data' => $transaction->load(['borrower', 'items', 'staff']),
            ]);
        });
    }

    public function markReturned(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'conditions' => ['sometimes', 'array'],
            'conditions.*.item_id' => ['required', 'integer', 'distinct'],
            'conditions.*.condition_after' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($request, $transaction, $validated): JsonResponse {
            $transaction = Transaction::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if (!in_array($transaction->status, ['Borrowed', 'Overdue'], true)) {
                return response()->json([
                    'message' => 'Only borrowed or overdue transactions can be returned.',
                ], 409);
            }

            $items = Item::query()
                ->whereIn('id', $transaction->items->modelKeys())
                ->lockForUpdate()
                ->get();

            $conditionByItem = collect($validated['conditions'] ?? [])->keyBy('item_id');

            if ($conditionByItem->keys()->diff($items->modelKeys())->isNotEmpty()) {
                return response()->json([
                    'message' => 'A condition was provided for an item not in this borrowing.',
                ], 422);
            }

            $transaction->update([
                'status' => 'Returned',
                'staff_id' => $request->user()->id,
            ]);

            foreach ($items as $item) {
                $updates = ['availability_status' => 'Available'];

                if ($conditionByItem->has($item->id)) {
                    $updates['condition_after'] =
                        $conditionByItem->get($item->id)['condition_after'] ?? null;
                }

                $item->update($updates);
            }

            return response()->json([
                'message' => 'Items marked as returned.',
                'data' => $transaction->load(['borrower', 'items', 'staff']),
            ]);
        });
    }
}