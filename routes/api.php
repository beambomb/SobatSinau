<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// 1. Public Auth Route
Route::post('/login', [AuthController::class, 'login']);

// 2. Protected Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'roles' => $request->user()->getRoleNames(),
        ]);
    });

    // CRUD Kelas untuk Guru & Admin
    Route::middleware('role:guru|admin')->group(function () {
        Route::apiResource('classrooms', \App\Http\Controllers\ClassroomController::class);
    });
});