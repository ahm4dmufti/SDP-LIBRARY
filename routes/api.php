<?php

use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffCONT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/staff-me', [StaffAuthController::class, 'me']);
    Route::post('/staff-logout', [StaffAuthController::class, 'logout']);
    Route::get('/staff', [StaffCONT::class, 'index']);
    Route::post('/staff', [StaffCONT::class, 'store']);
    Route::get('/staff/{id}', [StaffCONT::class, 'show']);
    Route::put('/staff/{id}', [StaffCONT::class, 'update']);
    Route::delete('/staff/{id}', [StaffCONT::class, 'destroy']);
});

Route::post('/staff/login', [StaffAuthController::class, 'login']);
