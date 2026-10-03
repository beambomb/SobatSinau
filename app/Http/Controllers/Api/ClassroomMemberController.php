<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomMemberController extends Controller
{
    public function index(Request $request, Classroom $classroom): JsonResponse
    {
        if (! $request->user()->isAdmin() && ! $classroom->isTaughtBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Hanya pengajar kelas yang dapat melihat anggota.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $classroom->students()->select('users.id', 'users.name', 'users.email')->orderBy('users.name')->get(),
        ]);
    }

    public function destroy(Request $request, Classroom $classroom, User $student): JsonResponse
    {
        if (! $request->user()->isAdmin() && ! $classroom->isTaughtBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak mengelola anggota kelas ini.'], 403);
        }

        $removed = $classroom->students()->detach($student->id);

        if ($removed === 0) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tersebut tidak terdaftar di kelas ini.'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'Siswa berhasil dikeluarkan dari kelas.']);
    }
}
