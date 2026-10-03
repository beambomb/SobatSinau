<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $user->isAdmin() ? Classroom::query() : $user->teachingClassrooms();

        $classrooms = $query
            ->with('teacher:id,name,email')
            ->withCount(['students', 'posts', 'assignments'])
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $classrooms]);
    }

    public function store(StoreClassroomRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() && ! $user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya guru atau admin yang dapat membuat kelas.'], 403);
        }

        $classroom = Classroom::create([
            'teacher_id' => $user->id,
            'title' => $request->string('title')->toString(),
            'subject' => $request->input('subject'),
            'code' => Classroom::generateUniqueCode(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dibuat.',
            'data' => $classroom->load('teacher:id,name,email')->loadCount(['students', 'posts', 'assignments']),
        ], 201);
    }

    public function show(Request $request, Classroom $classroom): JsonResponse
    {
        if (! $classroom->isAccessibleBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan anggota kelas ini.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $classroom
                ->load('teacher:id,name,email')
                ->loadCount(['students', 'posts', 'assignments']),
        ]);
    }

    public function update(StoreClassroomRequest $request, Classroom $classroom): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak mengubah kelas ini.'], 403);
        }

        $classroom->update($request->only(['title', 'subject']));

        return response()->json(['status' => 'success', 'message' => 'Kelas berhasil diperbarui.', 'data' => $classroom->fresh()->load('teacher:id,name,email')]);
    }

    public function destroy(Request $request, Classroom $classroom): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak menghapus kelas ini.'], 403);
        }

        $classroom->delete();

        return response()->json(['status' => 'success', 'message' => 'Kelas berhasil dihapus.']);
    }
}
