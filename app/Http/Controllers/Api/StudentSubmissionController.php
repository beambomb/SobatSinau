<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentSubmissionController extends Controller
{
    public function mySubmission(Request $request, Assignment $assignment): JsonResponse
    {
        if (! $this->canAccess($request, $assignment)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak memiliki akses ke tugas ini.'], 403);
        }

        $submission = $assignment->submissions()->where('student_id', $request->user()->id)->first();

        return response()->json(['status' => 'success', 'data' => $submission]);
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent() || ! $assignment->classroom->hasStudent($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan siswa di kelas tugas ini.'], 403);
        }

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $submission = $assignment->submissions()->where('student_id', $user->id)->first();

        if ($submission?->isGraded()) {
            return response()->json(['status' => 'error', 'message' => 'Tugas yang sudah dinilai tidak dapat dikirim ulang.'], 422);
        }

        if (! $submission && ! $request->hasFile('file')) {
            return response()->json(['status' => 'error', 'message' => 'Lampirkan file jawaban sebelum mengirim tugas.'], 422);
        }

        $filePath = $submission?->file_path;
        $fileName = $submission?->file_name;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions', 'public');
            $fileName = $request->file('file')->getClientOriginalName();
            if ($submission?->file_path) {
                Storage::disk('public')->delete($submission->file_path);
            }
        }

        $submission ??= new Submission(['assignment_id' => $assignment->id, 'student_id' => $user->id]);
        $submission->fill([
            'file_path' => $filePath,
            'file_name' => $fileName,
            'notes' => $validated['notes'] ?? null,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
        ])->save();

        return response()->json(['status' => 'success', 'message' => 'Tugas berhasil dikirim.', 'data' => $submission->fresh()], 201);
    }

    public function unsubmit(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();
        $submission = $assignment->submissions()->where('student_id', $user->id)->first();

        if (! $user->isStudent() || ! $submission) {
            return response()->json(['status' => 'error', 'message' => 'Belum ada pengumpulan tugas.'], 404);
        }

        if ($submission->isGraded()) {
            return response()->json(['status' => 'error', 'message' => 'Tugas yang sudah dinilai tidak dapat ditarik.'], 422);
        }

        $submission->delete();

        return response()->json(['status' => 'success', 'message' => 'Pengumpulan tugas berhasil ditarik.']);
    }

    private function canAccess(Request $request, Assignment $assignment): bool
    {
        $user = $request->user();

        return $user->isAdmin() || $assignment->classroom->isTaughtBy($user) || ($user->isStudent() && $assignment->classroom->hasStudent($user));
    }
}
