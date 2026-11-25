<?php

use App\Http\Controllers\Api\BorrowerController;
use App\Http\Controllers\Api\BorrowingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TouristController;
use App\Http\Controllers\Api\TouristSpotController;
use App\Http\Controllers\Api\TouristVisitController;
use App\Http\Controllers\Api\TransactionArchiveController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\TransactionCommentController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/items', [ItemController::class, 'index']);
Route::get('/tourist-spots', [TouristSpotController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole']);
});

Route::middleware([
    'auth:sanctum',
    'role:admin,editor,viewer,commenter',
])->group(function () {
    Route::get('/borrowings', [BorrowingController::class, 'index']);
    Route::get('/reports/borrowings', [ReportController::class, 'borrowings']);
    Route::get('/reports/overdue', [ReportController::class, 'overdue']);
    Route::get('/reports/repeat-tourists', [ReportController::class, 'repeatTourists']);
    Route::get('/reports/history', [ReportController::class, 'history']);

    Route::get('/tourists', [TouristController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/borrowers', [BorrowerController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:admin,editor'])->group(function () {
    Route::post('/visits', [TouristVisitController::class, 'store']);
    Route::post('/borrowings', [BorrowingController::class, 'store']);
    Route::get('/borrowers/archived', [BorrowerController::class, 'archived']);
    Route::patch('/borrowers/{borrower}/archive', [BorrowerController::class, 'archive']);
    Route::patch('/borrowers/{borrower}/restore', [BorrowerController::class, 'restore']);
    Route::get('/tourists/archived', [TouristController::class, 'archived']);
    Route::patch('/tourists/{tourist}/archive', [TouristController::class, 'archive']);
    Route::patch('/tourists/{tourist}/restore', [TouristController::class, 'restore']);
    Route::post('/tourist-spots/{touristSpot}/image', [TouristSpotController::class, 'uploadImage']);
    Route::patch('/borrowings/{transaction}/reject', [BorrowingController::class, 'reject']);

    Route::get('/items/archived', [ItemController::class, 'archived']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::patch('/items/{item}', [ItemController::class, 'update']);
    Route::patch('/items/{item}/archive', [ItemController::class, 'archive']);
    Route::patch('/items/{item}/restore', [ItemController::class, 'restore']);

    Route::get('/transactions/archived', [TransactionArchiveController::class, 'index']);
    Route::patch('/transactions/{transaction}/archive', [TransactionArchiveController::class, 'archive'])
        ->whereNumber('transaction');
    Route::patch('/transactions/{transaction}/restore', [TransactionArchiveController::class, 'restore'])
        ->whereNumber('transaction');

    Route::get('/tourist-spots/archived', [TouristSpotController::class, 'archived']);
    Route::post('/tourist-spots', [TouristSpotController::class, 'store']);
    Route::patch('/tourist-spots/{touristSpot}', [TouristSpotController::class, 'update']);
    Route::patch('/tourist-spots/{touristSpot}/archive', [TouristSpotController::class, 'archive']);
    Route::patch('/tourist-spots/{touristSpot}/restore', [TouristSpotController::class, 'restore']);

    Route::patch('/borrowings/{transaction}/approve', [BorrowingController::class, 'approve']);
    Route::patch('/borrowings/{transaction}/return', [BorrowingController::class, 'markReturned']);
    Route::patch('/visits/{transaction}/depart', [TouristVisitController::class, 'depart']);
});

Route::middleware(['auth:sanctum', 'role:admin,editor,commenter'])
    ->patch('/transactions/{transaction}/remarks', [
        TransactionCommentController::class,
        'updateRemarks',
    ])
    ->whereNumber('transaction');

Route::get('/items/{item}', [ItemController::class, 'show'])
    ->whereNumber('item');

Route::get('/tourist-spots/{touristSpot}', [TouristSpotController::class, 'show'])
    ->whereNumber('touristSpot');