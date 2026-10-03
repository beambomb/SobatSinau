<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isStudent()) {
            return response()->json(['status' => 'error', 'message' => 'Fitur ini khusus siswa.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $request->user()->enrolledClassrooms()
                ->with('teacher:id,name,email')
                ->withCount(['students', 'posts', 'assignments'])
                ->latest('classroom_user.created_at')
                ->get(),
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate(['code' => ['required', 'string', 'size:7']]);

        if (! $user->isStudent()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya siswa yang dapat bergabung ke kelas.'], 403);
        }

        $classroom = Classroom::where('code', strtolower($validated['code']))->first();

        if (! $classroom) {
            return response()->json(['status' => 'error', 'message' => 'Kode kelas tidak ditemukan.'], 404);
        }

        if ($classroom->teacher_id === $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Pemilik kelas tidak dapat bergabung sebagai siswa.'], 422);
        }

        if ($classroom->hasStudent($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu sudah terdaftar di kelas ini.'], 422);
        }

        $classroom->students()->attach($user->id);

        return response()->json(['status' => 'success', 'message' => 'Berhasil bergabung ke kelas.', 'data' => $classroom->load('teacher:id,name,email')], 201);
    }

    public function leave(Request $request, Classroom $classroom): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent() || ! $classroom->hasStudent($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak terdaftar di kelas ini.'], 422);
        }

        $classroom->students()->detach($user->id);

        return response()->json(['status' => 'success', 'message' => 'Kamu berhasil keluar dari kelas.']);
    }
}
