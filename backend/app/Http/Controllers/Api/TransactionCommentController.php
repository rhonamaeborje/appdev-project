<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionCommentController extends Controller
{
    public function updateRemarks(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->is_archived) {
            return response()->json([
                'message' => 'Archived transactions cannot be commented on.',
            ], 409);
        }

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:5000'],
        ]);

        $transaction->update([
            'remarks' => $validated['remarks'],
        ]);

        return response()->json([
            'message' => 'Transaction remark saved.',
            'data' => [
                'transaction_id' => $transaction->id,
                'remarks' => $transaction->remarks,
            ],
        ]);
    }
}