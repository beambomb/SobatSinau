<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LmsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'guru', 'siswa'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_user_can_login_and_receive_a_sanctum_token(): void
    {
        $user = $this->makeUser('guru@pintaria.test', 'guru');

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonPath('user.email', $user->email)->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_teacher_can_create_a_classroom(): void
    {
        $teacher = $this->makeUser('teacher@pintaria.test', 'guru');

        $response = $this->actingAs($teacher, 'sanctum')->postJson('/api/classrooms', [
            'title' => 'Dasar Pemrograman',
            'subject' => 'Informatika',
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Dasar Pemrograman');
        $this->assertDatabaseHas('classrooms', ['title' => 'Dasar Pemrograman', 'teacher_id' => $teacher->id]);
    }

    public function test_student_can_join_and_view_a_classroom(): void
    {
        $teacher = $this->makeUser('teacher@pintaria.test', 'guru');
        $student = $this->makeUser('student@pintaria.test', 'siswa');
        $classroom = Classroom::create(['teacher_id' => $teacher->id, 'title' => 'Kelas API', 'subject' => 'Backend', 'code' => 'abc2345']);

        $this->actingAs($student, 'sanctum')->postJson('/api/classrooms/join', ['code' => $classroom->code])->assertCreated();
        $this->actingAs($student, 'sanctum')->getJson('/api/classrooms/'.$classroom->id)->assertOk()->assertJsonPath('data.id', $classroom->id);
        $this->assertDatabaseHas('classroom_user', ['classroom_id' => $classroom->id, 'user_id' => $student->id]);
    }

    public function test_student_cannot_create_a_classroom(): void
    {
        $student = $this->makeUser('student@pintaria.test', 'siswa');

        $this->actingAs($student, 'sanctum')->postJson('/api/classrooms', ['title' => 'Tidak boleh'])->assertForbidden();
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create(['name' => ucfirst($role).' Pintaria', 'email' => $email, 'password' => Hash::make('password123')]);
        $user->assignRole($role);

        return $user;
    }
}
