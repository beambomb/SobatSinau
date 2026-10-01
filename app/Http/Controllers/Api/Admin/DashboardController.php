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
    /**
     * Menampilkan statistik dan ringkasan platform LMS untuk Admin.
     */
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

    /**
     * Pengawasan global: Melihat seluruh kelas di sistem beserta pengajar dan jumlah murid.
     */
    public function classrooms(Request $request): JsonResponse
    {
        $query = Classroom::with('teacher:id,name,email')
            ->withCount(['students', 'posts', 'assignments'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $classrooms = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $classrooms,
        ]);
    }

    /**
     * Super Admin Override: Menghapus kelas manapun di sistem.
     */
    public function destroyClassroom(Classroom $classroom): JsonResponse
    {
        $title = $classroom->title;
        $classroom->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Kelas {$title} berhasil dihapus oleh Admin.",
        ]);
    }
}
