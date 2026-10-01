<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Classroom;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Menampilkan seluruh postingan materi / pengumuman di forum kelas.
     */
    public function index(Classroom $classroom): JsonResponse
    {
        $user = Auth::user();

        // Otorisasi: Hanya guru kelas, siswa yang terdaftar, atau admin yang bisa melihat
        $isTeacher = $classroom->teacher_id === $user->id;
        $isStudent = $classroom->students()->where('user_id', $user->id)->exists();

        if (! $user->hasRole('admin') && ! $isTeacher && ! $isStudent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 403);
        }

        $posts = $classroom->posts()
            ->with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ]);
    }

    /**
     * Guru/Siswa membuat postingan baru (termasuk upload media jika ada).
     */
    public function store(StorePostRequest $request, Classroom $classroom): JsonResponse
    {
        $user = Auth::user();

        $isTeacher = $classroom->teacher_id === $user->id;
        $isStudent = $classroom->students()->where('user_id', $user->id)->exists();

        if (! $user->hasRole('admin') && ! $isTeacher && ! $isStudent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan anggota kelas ini.',
            ], 403);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('posts', 'public');
        }

        $post = $classroom->posts()->create([
            'user_id' => $user->id,
            'content' => $request->content,
            'type' => $request->input('type', 'announcement'),
            'attachment_path' => $attachmentPath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Postingan berhasil dipublikasikan!',
            'data' => $post->load('user:id,name,email'),
        ], 201);
    }

    /**
     * Hapus postingan (hanya pembuat post, guru pemilik kelas, atau admin).
     */
    public function destroy(Post $post): JsonResponse
    {
        $user = Auth::user();
        $classroom = $post->classroom;

        $isAuthor = $post->user_id === $user->id;
        $isTeacher = $classroom && $classroom->teacher_id === $user->id;

        if (! $user->hasRole('admin') && ! $isAuthor && ! $isTeacher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak menghapus postingan ini.',
            ], 403);
        }

        // Hapus file dari storage jika ada
        if ($post->attachment_path && Storage::disk('public')->exists($post->attachment_path)) {
            Storage::disk('public')->delete($post->attachment_path);
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Postingan berhasil dihapus!',
        ]);
    }
}
