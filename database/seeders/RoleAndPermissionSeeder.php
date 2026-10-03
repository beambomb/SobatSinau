<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'guru', 'siswa'] as $role) {
            \Spatie\Permission\Models\Role::findOrCreate($role, 'web');
        }

        $admin = $this->user('admin@lms.test', 'Admin Pintaria', 'admin');
        $teacher = $this->user('guru@lms.test', 'Budi Santoso', 'guru');
        $student = $this->user('siswa@lms.test', 'Ayu Lestari', 'siswa');
        $studentTwo = $this->user('siswa2@lms.test', 'Raka Wijaya', 'siswa');

        $classroom = Classroom::firstOrCreate(
            ['code' => 'pintari'],
            ['teacher_id' => $teacher->id, 'title' => 'Kelas Pemrograman Web', 'subject' => 'Teknologi Informasi'],
        );
        $classroom->students()->syncWithoutDetaching([$student->id, $studentTwo->id]);

        Post::firstOrCreate(
            ['classroom_id' => $classroom->id, 'content' => 'Selamat datang di kelas Pintaria! Gunakan forum ini untuk berdiskusi.'],
            ['user_id' => $teacher->id, 'type' => 'announcement'],
        );

        Assignment::firstOrCreate(
            ['classroom_id' => $classroom->id, 'title' => 'Membuat halaman profil responsif'],
            ['teacher_id' => $teacher->id, 'instructions' => 'Buat halaman profil sederhana dengan HTML dan CSS.', 'due_date' => now()->addDays(7), 'max_points' => 100],
        );
    }

    private function user(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password123')],
        );
        $user->syncRoles([$role]);

        return $user;
    }
}
