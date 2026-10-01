<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomMemberController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\StudentClassroomController;
use App\Http\Controllers\Api\StudentSubmissionController;
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

    // Komentar Diskusi Postingan
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Tugas, Soal, dan Penilaian (Assignments & Grading)
    Route::get('/classrooms/{classroom}/assignments', [AssignmentController::class, 'index']);
    Route::post('/classrooms/{classroom}/assignments', [AssignmentController::class, 'store']);
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);
    Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions']);
    Route::post('/submissions/{submission}/grade', [AssignmentController::class, 'grade']);

    // Fitur Kelas Khusus Murid (Siswa)
    Route::get('/my-classrooms', [StudentClassroomController::class, 'index']);
    Route::post('/classrooms/join', [StudentClassroomController::class, 'join']);
    Route::post('/classrooms/{classroom}/leave', [StudentClassroomController::class, 'leave']);

    // Fitur Pengumpulan Tugas Murid
    Route::get('/assignments/{assignment}/my-submission', [StudentSubmissionController::class, 'mySubmission']);
    Route::post('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'submit']);
    Route::post('/assignments/{assignment}/unsubmit', [StudentSubmissionController::class, 'unsubmit']);
});
