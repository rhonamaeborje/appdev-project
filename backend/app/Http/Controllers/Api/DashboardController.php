<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\TouristSpot;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now('Asia/Manila')->toDateString();

        return response()->json([
            'data' => [
                'active_items' => Item::query()
                    ->where('is_archived', false)
                    ->count(),

                'active_tourist_spots' => TouristSpot::query()
                    ->where('is_archived', false)
                    ->count(),

                'pending_borrowings' => Transaction::query()
                    ->whereNotNull('borrower_id')
                    ->where('is_archived', false)
                    ->where('status', 'Pending')
                    ->count(),

                'overdue_borrowings' => Transaction::query()
                    ->whereNotNull('borrower_id')
                    ->where('is_archived', false)
                    ->whereIn('status', ['Borrowed', 'Overdue'])
                    ->whereNotNull('return_date')
                    ->whereDate('return_date', '<', $today)
                    ->count(),

                'visits_today' => Transaction::query()
                    ->whereNotNull('tourist_id')
                    ->where('is_archived', false)
                    ->whereDate('date_borrowed_or_visited', $today)
                    ->count(),
            ],
        ]);
    }
}