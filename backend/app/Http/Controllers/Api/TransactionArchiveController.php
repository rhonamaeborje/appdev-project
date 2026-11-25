<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionArchiveController extends Controller
{
    public function index(): JsonResponse
    {
        $transactions = Transaction::query()
            ->with(['borrower', 'tourist', 'staff', 'items'])
            ->where('is_archived', true)
            ->latest()
            ->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }

    public function archive(Transaction $transaction): JsonResponse
    {
        if ($transaction->is_archived) {
            return response()->json([
                'message' => 'Transaction is already archived.',
            ], 409);
        }

        if (!in_array($transaction->status, ['Completed', 'Returned'], true)) {
            return response()->json([
                'message' => 'Only completed visits or returned borrowings can be archived.',
            ], 409);
        }

        $transaction->update(['is_archived' => true]);

        return response()->json([
            'message' => 'Transaction archived successfully.',
            'data' => $transaction->fresh()->load(['borrower', 'tourist', 'staff', 'items']),
        ]);
    }

    public function restore(Transaction $transaction): JsonResponse
    {
        if (!$transaction->is_archived) {
            return response()->json([
                'message' => 'Transaction is not archived.',
            ], 409);
        }

        $transaction->update(['is_archived' => false]);

        return response()->json([
            'message' => 'Transaction restored successfully.',
            'data' => $transaction->fresh()->load(['borrower', 'tourist', 'staff', 'items']),
        ]);
    }
}