<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    /**
     * Menambahkan pengguna baru dan menetapkan role oleh Admin.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'status' => 'success',
            'message' => "Pengguna {$user->name} dengan role {$request->role} berhasil dibuat!",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /**
     * Memperbarui profil dan role pengguna oleh Admin.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sinkronisasi role baru
        $user->syncRoles([$request->role]);

        return response()->json([
            'status' => 'success',
            'message' => "Profil dan role {$user->name} berhasil diperbarui!",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Menghapus pengguna dari sistem.
     */
    public function destroy(User $user): JsonResponse
    {
        // Cegah admin menghapus akunnya sendiri yang sedang login
        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak dapat menghapus akun adminmu sendiri yang sedang aktif.',
            ], 400);
        }

        $userName = $user->name;
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Pengguna {$userName} berhasil dihapus dari sistem.",
        ]);
    }
}
