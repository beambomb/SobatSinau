<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar tugas di dalam kelas.
     */
    public function index(Classroom $classroom): JsonResponse
    {
        $assignments = $classroom->assignments()
            ->with('teacher:id,name,email')
            ->withCount('submissions')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $assignments,
        ]);
    }

    /**
     * Guru membuat tugas/soal baru (termasuk upload file soal jika ada).
     */
    public function store(StoreAssignmentRequest $request, Classroom $classroom): JsonResponse
    {
        $user = Auth::user();

        // Otorisasi: Pastikan guru pemilik kelas atau admin
        if (! $user->hasRole('admin') && $classroom->teacher_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak membuat tugas di kelas ini.',
            ], 403);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('assignments', 'public');
        }

        $assignment = $classroom->assignments()->create([
            'teacher_id' => $user->id,
            'title' => $request->title,
            'instructions' => $request->instructions,
            'due_date' => $request->due_date,
            'max_points' => $request->input('max_points', 100),
            'attachment_path' => $attachmentPath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil dibuat!',
            'data' => $assignment,
        ], 201);
    }

    /**
     * Menampilkan detail suatu tugas.
     */
    public function show(Assignment $assignment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $assignment->load('teacher:id,name,email', 'classroom:id,title,code'),
        ]);
    }

    /**
     * Guru melihat seluruh pengumpulan tugas (submissions) dari para murid.
     */
    public function submissions(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        $classroom = $assignment->classroom;

        if (! $user->hasRole('admin') && $classroom->teacher_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan pengajar di kelas ini.',
            ], 403);
        }

        $submissions = $assignment->submissions()
            ->with('student:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'max_points' => $assignment->max_points,
            ],
            'data' => $submissions,
        ]);
    }

    /**
     * Guru memberi nilai dan catatan ke tugas yang dikumpulkan siswa.
     */
    public function grade(Request $request, Submission $submission): JsonResponse
    {
        $user = Auth::user();
        $assignment = $submission->assignment;
        $classroom = $assignment->classroom;

        if (! $user->hasRole('admin') && $classroom->teacher_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak menilai tugas ini.',
            ], 403);
        }

        $validated = $request->validate([
            'grade' => ['required', 'integer', 'min:0', 'max:'.$assignment->max_points],
            'notes' => ['nullable', 'string'],
        ]);

        $submission->update([
            'grade' => $validated['grade'],
            'notes' => $validated['notes'] ?? $submission->notes,
            'status' => 'graded',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai berhasil disimpan!',
            'data' => $submission->fresh(['student:id,name,email']),
        ]);
    }

    /**
     * Hapus tugas.
     */
    public function destroy(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        $classroom = $assignment->classroom;

        if (! $user->hasRole('admin') && $classroom->teacher_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak menghapus tugas ini.',
            ], 403);
        }

        if ($assignment->attachment_path && Storage::disk('public')->exists($assignment->attachment_path)) {
            Storage::disk('public')->delete($assignment->attachment_path);
        }

        $assignment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil dihapus!',
        ]);
    }
}
