<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClassroomMemberController extends Controller
{
    /**
     * Menampilkan daftar siswa yang terdaftar di dalam kelas.
     */
    public function index(Classroom $classroom): JsonResponse
    {
        // Otorisasi: Pastikan guru pemilik kelas atau admin
        if (! Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan pengajar di kelas ini.',
            ], 403);
        }

        $students = $classroom->students()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $students,
        ]);
    }

    /**
     * Mengeluarkan (kick) siswa dari kelas.
     */
    public function destroy(Classroom $classroom, User $student): JsonResponse
    {
        if (! Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak mengelola anggota kelas ini.',
            ], 403);
        }

        // Lepaskan relasi pivot classroom_user
        $classroom->students()->detach($student->id);

        return response()->json([
            'status' => 'success',
            'message' => "Siswa {$student->name} berhasil dikeluarkan dari kelas.",
        ]);
    }
}
