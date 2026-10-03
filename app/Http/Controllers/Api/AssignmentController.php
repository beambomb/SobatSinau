<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    public function index(Request $request, Classroom $classroom): JsonResponse
    {
        if (! $classroom->isAccessibleBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan anggota kelas ini.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $classroom->assignments()
                ->with('teacher:id,name,email')
                ->withCount('submissions')
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreAssignmentRequest $request, Classroom $classroom): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak membuat tugas di kelas ini.'], 403);
        }

        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('assignments', 'public') : null;
        $assignment = $classroom->assignments()->create([
            'teacher_id' => $user->id,
            'title' => $request->string('title')->toString(),
            'instructions' => $request->input('instructions'),
            'due_date' => $request->input('due_date'),
            'max_points' => $request->integer('max_points', 100),
            'attachment_path' => $path,
            'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Tugas berhasil dibuat.', 'data' => $assignment->load('teacher:id,name,email')], 201);
    }

    public function show(Request $request, Assignment $assignment): JsonResponse
    {
        if (! $assignment->classroom->isAccessibleBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak memiliki akses ke tugas ini.'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $assignment->load('teacher:id,name,email', 'classroom:id,title,code')]);
    }

    public function submissions(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $assignment->classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Hanya pengajar yang dapat melihat pengumpulan.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'assignment' => $assignment->only(['id', 'title', 'max_points']),
            'data' => $assignment->submissions()->with('student:id,name,email')->latest()->get(),
        ]);
    }

    public function grade(Request $request, Submission $submission): JsonResponse
    {
        $user = $request->user();
        $assignment = $submission->assignment;
        if (! $user->isAdmin() && ! $assignment->classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak menilai tugas ini.'], 403);
        }

        $validated = $request->validate([
            'grade' => ['required', 'integer', 'min:0', 'max:'.$assignment->max_points],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        $submission->update([
            'grade' => $validated['grade'],
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Nilai berhasil disimpan.', 'data' => $submission->fresh(['student:id,name,email'])]);
    }

    public function destroy(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $assignment->classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak menghapus tugas ini.'], 403);
        }

        if ($assignment->attachment_path) {
            Storage::disk('public')->delete($assignment->attachment_path);
        }
        $assignment->delete();

        return response()->json(['status' => 'success', 'message' => 'Tugas berhasil dihapus.']);
    }
}
