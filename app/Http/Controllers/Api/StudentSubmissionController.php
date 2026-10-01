<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudentSubmissionController extends Controller
{
    /**
     * Siswa melihat status pengumpulan tugas & nilai mereka sendiri.
     */
    public function mySubmission(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        $classroom = $assignment->classroom;

        // Otorisasi: Siswa harus terdaftar di kelas
        if (! $user->enrolledClassrooms()->where('classroom_id', $classroom->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 403);
        }

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        return response()->json([
            'status' => 'success',
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'due_date' => $assignment->due_date,
                'max_points' => $assignment->max_points,
            ],
            'submission' => $submission ? [
                'id' => $submission->id,
                'file_url' => $submission->file_path ? asset('storage/'.$submission->file_path) : null,
                'notes' => $submission->notes,
                'status' => $submission->status,
                'grade' => $submission->grade,
                'submitted_at' => $submission->submitted_at,
            ] : null,
        ]);
    }

    /**
     * Siswa mengumpulkan tugas (termasuk upload file jawaban & catatan).
     */
    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        $classroom = $assignment->classroom;

        if (! $user->enrolledClassrooms()->where('classroom_id', $classroom->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 403);
        }

        $request->validate([
            'file' => ['nullable', 'file', 'max:20480'], // max 20MB
            'notes' => ['nullable', 'string'],
        ]);

        if (! $request->hasFile('file') && empty($request->notes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Harap lampirkan file jawaban atau tuliskan catatan jawabanmu.',
            ], 422);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions', 'public');
        }

        $submission = Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $user->id,
            ],
            [
                'file_path' => $filePath,
                'notes' => $request->notes,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil dikumpulkan!',
            'data' => [
                'id' => $submission->id,
                'file_url' => $submission->file_path ? asset('storage/'.$submission->file_path) : null,
                'notes' => $submission->notes,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at,
            ],
        ], 201);
    }

    /**
     * Fitur Tarik Tugas (Unsubmit) jika siswa salah mengirim file.
     */
    public function unsubmit(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        if (! $submission || $submission->status === 'unsubmitted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu belum mengumpulkan tugas ini.',
            ], 400);
        }

        // Jika sudah dinilai guru, tidak boleh ditarik kembali
        if ($submission->status === 'graded') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tugas ini sudah dinilai oleh guru dan tidak dapat ditarik kembali.',
            ], 400);
        }

        // Hapus file lama di storage
        if ($submission->file_path && Storage::disk('public')->exists($submission->file_path)) {
            Storage::disk('public')->delete($submission->file_path);
        }

        $submission->update([
            'file_path' => null,
            'status' => 'unsubmitted',
            'submitted_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil ditarik kembali (unsubmitted). Kamu sekarang bisa mengunggah ulang file yang benar.',
        ]);
    }
}
