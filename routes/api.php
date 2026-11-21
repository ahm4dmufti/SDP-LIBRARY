<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffCONT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:staff')->group(function () {
    Route::get('/staff-me', [StaffAuthController::class, 'me']);
    Route::post('/staff-logout', [StaffAuthController::class, 'logout']);
    Route::get('/staff', [StaffCONT::class, 'index']);
    Route::post('/staff', [StaffCONT::class, 'store']);
    Route::get('/staff/{id}', [StaffCONT::class, 'show']);
    Route::put('/staff/{id}', [StaffCONT::class, 'update']);
    Route::delete('/staff/{id}', [StaffCONT::class, 'destroy']);
    Route::post('/category', [CategoryController::class, 'store']);
    Route::put('/category/{category}', [CategoryController::class, 'update']);
    Route::delete('/category/{category}', [CategoryController::class, 'destroy']);
    Route::post('/book', [BookController::class, 'store']);
    Route::put('/book/{book}', [BookController::class, 'update']);
    Route::delete('/book/{book}', [BookController::class, 'destroy']);
    Route::put('/borrowings/return', [BorrowingsController::class, 'markAsReturned']);
    Route::get('/borrowings/all', [BorrowingsController::class, 'all']);
    Route::get('/borrowings/overdue', [BorrowingsController::class, 'overdue']);
    Route::get('/customer', [CustomerController::class, 'index']);
    Route::get('/customer/{customer}', [CustomerController::class, 'show']);
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/customer-me', [CustomerAuthController::class, 'me']);
    Route::post('/customer-logout', [CustomerAuthController::class, 'logout']);
    Route::post('/borrowings', [BorrowingsController::class, 'borrow']);
    Route::get('/borrowings', [BorrowingsController::class, 'borrowings']);
    Route::delete('/borrowings/{id}', [BorrowingsController::class, 'delete']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/category/{category}', [CategoryController::class, 'show']);
    Route::get('/book', [BookController::class, 'index']);
    Route::get('/book/{book}', [BookController::class, 'show']);
});

Route::post('/staff/login', [StaffAuthController::class, 'login']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);
Route::post('/customer', [CustomerController::class, 'store']);
