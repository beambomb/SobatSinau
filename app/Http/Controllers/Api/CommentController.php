<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Menampilkan seluruh komentar di suatu postingan.
     */
    public function index(Post $post): JsonResponse
    {
        $user = Auth::user();
        $classroom = $post->classroom;

        $isTeacher = $classroom && $classroom->teacher_id === $user->id;
        $isStudent = $classroom && $classroom->students()->where('user_id', $user->id)->exists();

        if (! $user->hasRole('admin') && ! $isTeacher && ! $isStudent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 403);
        }

        $comments = $post->comments()
            ->with('user:id,name,email')
            ->oldest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $comments,
        ]);
    }

    /**
     * Menambahkan komentar ke postingan.
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        $user = Auth::user();
        $classroom = $post->classroom;

        $isTeacher = $classroom && $classroom->teacher_id === $user->id;
        $isStudent = $classroom && $classroom->students()->where('user_id', $user->id)->exists();

        if (! $user->hasRole('admin') && ! $isTeacher && ! $isStudent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar berhasil ditambahkan!',
            'data' => $comment->load('user:id,name,email'),
        ], 201);
    }

    /**
     * Hapus komentar (hanya pembuat komentar, guru pemilik kelas, atau admin).
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $user = Auth::user();
        $classroom = $comment->post ? $comment->post->classroom : null;

        $isAuthor = $comment->user_id === $user->id;
        $isTeacher = $classroom && $classroom->teacher_id === $user->id;

        if (! $user->hasRole('admin') && ! $isAuthor && ! $isTeacher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak menghapus komentar ini.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar berhasil dihapus!',
        ]);
    }
}
