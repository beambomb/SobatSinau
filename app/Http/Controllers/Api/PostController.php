<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Classroom;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request, Classroom $classroom): JsonResponse
    {
        if (! $classroom->isAccessibleBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan anggota kelas ini.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $classroom->posts()
                ->with('user:id,name,email')
                ->withCount('comments')
                ->latest()
                ->get(),
        ]);
    }

    public function store(StorePostRequest $request, Classroom $classroom): JsonResponse
    {
        $user = $request->user();

        if (! $classroom->isAccessibleBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu bukan anggota kelas ini.'], 403);
        }

        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('posts', 'public') : null;
        $post = $classroom->posts()->create([
            'user_id' => $user->id,
            'content' => $request->string('content')->toString(),
            'type' => $request->input('type', 'discussion'),
            'attachment_path' => $path,
            'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Postingan berhasil dipublikasikan.',
            'data' => $post->load('user:id,name,email'),
        ], 201);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();
        $classroom = $post->classroom;

        if (! $user->isAdmin() && $post->user_id !== $user->id && ! $classroom->isTaughtBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak berhak menghapus postingan ini.'], 403);
        }

        if ($post->attachment_path) {
            Storage::disk('public')->delete($post->attachment_path);
        }
        $post->delete();

        return response()->json(['status' => 'success', 'message' => 'Postingan berhasil dihapus.']);
    }
}
