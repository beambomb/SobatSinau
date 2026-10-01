<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentClassroomController extends Controller
{
    /**
     * Menampilkan seluruh kelas yang sedang diikuti oleh siswa yang login.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $enrolledClassrooms = $user->enrolledClassrooms()
            ->with('teacher:id,name,email')
            ->latest('classroom_user.created_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $enrolledClassrooms,
        ]);
    }

    /**
     * Siswa bergabung ke kelas menggunakan kode unik (misal: "vnvi6s").
     */
    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = strtolower(trim($request->code));
        $classroom = Classroom::where('code', $code)->first();

        if (! $classroom) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode kelas tidak valid atau kelas tidak ditemukan.',
            ], 404);
        }

        $user = Auth::user();

        // Mencegah guru pemilik kelas bergabung ke kelasnya sendiri sebagai murid
        if ($classroom->teacher_id === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu adalah pengajar di kelas ini, tidak bisa bergabung sebagai siswa.',
            ], 400);
        }

        // Cek apakah siswa sudah pernah bergabung sebelumnya
        if ($user->enrolledClassrooms()->where('classroom_id', $classroom->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu sudah terdaftar di kelas ini.',
            ], 400);
        }

        // Daftarkan siswa ke kelas (simpan ke pivot classroom_user)
        $user->enrolledClassrooms()->attach($classroom->id);

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil bergabung ke kelas {$classroom->title}!",
            'data' => $classroom->load('teacher:id,name,email'),
        ], 200);
    }

    /**
     * Siswa keluar dari kelas (leave class).
     */
    public function leave(Classroom $classroom): JsonResponse
    {
        $user = Auth::user();

        if (! $user->enrolledClassrooms()->where('classroom_id', $classroom->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 400);
        }

        $user->enrolledClassrooms()->detach($classroom->id);

        return response()->json([
            'status' => 'success',
            'message' => "Kamu telah keluar dari kelas {$classroom->title}.",
        ]);
    }
}
