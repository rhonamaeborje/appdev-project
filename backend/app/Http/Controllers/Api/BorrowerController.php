<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BorrowerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Borrower::query()
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
                'contact_number',
                'email',
                'date_registered',
            ]),
        ]);
    }

    public function archived(): JsonResponse
    {
        $borrowers = Borrower::query()
            ->where('is_archived', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'address', 'contact_number', 'email', 'date_registered']);

        return response()->json(['data' => $borrowers]);
    }

    public function archive(Borrower $borrower): JsonResponse
    {
        if ($borrower->is_archived) {
            return response()->json(['message' => 'Borrower is already archived.'], 409);
        }

        $hasOpenBorrowing = Transaction::query()
            ->where('borrower_id', $borrower->id)
            ->where('is_archived', false)
            ->whereIn('status', ['Pending', 'Approved', 'Borrowed', 'Overdue'])
            ->exists();

        if ($hasOpenBorrowing) {
            return response()->json([
                'message' => 'Borrower has a pending or active borrowing and cannot be archived yet.',
            ], 409);
        }

        $borrower->is_archived = true;
        $borrower->save();

        return response()->json([
            'message' => 'Borrower archived successfully.',
            'data' => $borrower->fresh(),
        ]);
    }

    public function restore(Borrower $borrower): JsonResponse
    {
        if (!$borrower->is_archived) {
            return response()->json(['message' => 'Borrower is not archived.'], 409);
        }

        $borrower->is_archived = false;
        $borrower->save();

        return response()->json([
            'message' => 'Borrower restored successfully.',
            'data' => $borrower->fresh(),
        ]);
    }
}