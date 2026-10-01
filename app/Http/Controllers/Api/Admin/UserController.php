<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengguna dengan filter role & pencarian nama/email.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles:id,name')->latest();

        // Filter berdasarkan Role (misal: ?role=guru atau ?role=siswa)
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Pencarian berdasarkan Nama atau Email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Menampilkan detail informasi akun pengguna.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'teaching_classrooms_count' => $user->teachingClassrooms()->count(),
                'enrolled_classrooms_count' => $user->enrolledClassrooms()->count(),
                'created_at' => $user->created_at,
            ],
        ]);
    }
}
