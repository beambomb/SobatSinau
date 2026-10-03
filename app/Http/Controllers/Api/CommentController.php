<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Post $post): JsonResponse
    {
        if (! $post->classroom->isAccessibleBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan anggota kelas ini.'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $post->comments()->with('user:id,name,email')->oldest()->get()]);
    }

    public function store(Request $request, Post $post): JsonResponse
    {
        if (! $post->classroom->isAccessibleBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan anggota kelas ini.'], 403);
        }
        $validated = $request->validate(['content' => ['required', 'string', 'max:2000']]);
        $comment = $post->comments()->create(['user_id' => $request->user()->id, 'content' => $validated['content']]);

        return response()->json(['status' => 'success', 'message' => 'Komentar berhasil ditambahkan.', 'data' => $comment->load('user:id,name,email')], 201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && $comment->user_id !== $user->id && ! $comment->post->classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak menghapus komentar ini.'], 403);
        }
        $comment->delete();

        return response()->json(['status' => 'success', 'message' => 'Komentar berhasil dihapus.']);
    }
}
