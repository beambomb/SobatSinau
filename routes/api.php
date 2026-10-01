<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomMemberController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\ClassroomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        Route::apiResource('classrooms', ClassroomController::class);

        // Manajemen Anggota Kelas (Lihat & Kick Siswa)
        Route::get('/classrooms/{classroom}/students', [ClassroomMemberController::class, 'index']);
        Route::delete('/classrooms/{classroom}/students/{student}', [ClassroomMemberController::class, 'destroy']);
    });

    // Forum Stream, Materi, dan Pengumuman
    Route::get('/classrooms/{classroom}/posts', [PostController::class, 'index']);
    Route::post('/classrooms/{classroom}/posts', [PostController::class, 'store']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
});
