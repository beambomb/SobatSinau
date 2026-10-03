<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Post;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_users' => User::count(),
                    'total_admins' => User::role('admin')->count(),
                    'total_teachers' => User::role('guru')->count(),
                    'total_students' => User::role('siswa')->count(),
                    'total_classrooms' => Classroom::count(),
                    'total_posts' => Post::count(),
                    'total_assignments' => Assignment::count(),
                    'total_submissions' => Submission::count(),
                ],
                'latest_users' => User::with('roles:id,name')->latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
                'latest_classrooms' => Classroom::with('teacher:id,name,email')->latest()->take(5)->get(['id', 'title', 'code', 'teacher_id', 'created_at']),
            ],
        ]);
    }

    public function classrooms(Request $request): JsonResponse
    {
        $query = Classroom::with('teacher:id,name,email')->withCount(['students', 'posts', 'assignments'])->latest();
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($builder) => $builder->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        return response()->json(['status' => 'success', 'data' => $query->paginate(min($request->integer('per_page', 15), 50))]);
    }

    public function destroyClassroom(Classroom $classroom): JsonResponse
    {
        $classroom->delete();

        return response()->json(['status' => 'success', 'message' => 'Kelas berhasil dihapus oleh admin.']);
    }
}
