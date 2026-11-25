<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tourist;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function borrowings(): JsonResponse
    {
        $records = Transaction::query()
            ->with(['borrower', 'staff', 'items'])
            ->whereNotNull('borrower_id')
            ->where('is_archived', false)
            ->latest()
            ->get();

        return response()->json(['data' => $records]);
    }

    public function overdue(): JsonResponse
    {
        $today = now('Asia/Manila')->toDateString();

        $records = Transaction::query()
            ->with(['borrower', 'staff', 'items'])
            ->whereNotNull('borrower_id')
            ->where('is_archived', false)
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<', $today)
            ->orderBy('return_date')
            ->get();

        return response()->json(['data' => $records]);
    }

    public function repeatTourists(): JsonResponse
    {
        $visits = Transaction::query()
            ->with('tourist')
            ->whereNotNull('tourist_id')
            ->where('is_archived', false)
            ->latest('date_borrowed_or_visited')
            ->get()
            ->groupBy('tourist_id')
            ->filter(fn ($touristVisits) => $touristVisits->count() > 1)
            ->map(fn ($touristVisits) => [
                'tourist' => $touristVisits->first()->tourist,
                'visit_count' => $touristVisits->count(),
                'visits' => $touristVisits->values(),
            ])
            ->values();

        return response()->json(['data' => $visits]);
    }

    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:borrowing,visit'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Transaction::query()
            ->with(['borrower', 'tourist', 'staff', 'items'])
            ->latest('date_borrowed_or_visited')
            ->latest('id');

        if (($validated['type'] ?? null) === 'borrowing') {
            $query->whereNotNull('borrower_id');
        }

        if (($validated['type'] ?? null) === 'visit') {
            $query->whereNotNull('tourist_id');
        }

        if (!empty($validated['from'])) {
            $query->whereDate(
                'date_borrowed_or_visited',
                '>=',
                $validated['from']
            );
        }

        if (!empty($validated['to'])) {
            $query->whereDate(
                'date_borrowed_or_visited',
                '<=',
                $validated['to']
            );
        }

        return response()->json(['data' => $query->get()]);
    }
}