<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cache Spatie (wajib agar permission terbaru terbaca)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat Role
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleGuru = Role::firstOrCreate(['name' => 'guru']);
        $roleSiswa = Role::firstOrCreate(['name' => 'siswa']);

        // 3. Buat Akun Dummy untuk Pengujian

        // Akun Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@lms.test'],
            [
                'name' => 'Admin LMS',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->assignRole($roleAdmin);

        // Akun Guru
        $guru = User::firstOrCreate(
            ['email' => 'guru@lms.test'],
            [
                'name' => 'Pak Budi Guru',
                'password' => Hash::make('password123'),
            ]
        );
        $guru->assignRole($roleGuru);

        // Akun Siswa
        $siswa = User::firstOrCreate(
            ['email' => 'siswa@lms.test'],
            [
                'name' => 'Andi Siswa',
                'password' => Hash::make('password123'),
            ]
        );
        $siswa->assignRole($roleSiswa);
    }
}
