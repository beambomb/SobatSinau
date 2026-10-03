<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Remove owned classrooms (and their files) plus personal uploads before the user row is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->teachingClassrooms()->get()->each->delete();

            Storage::disk('public')->delete([
                ...$user->submissions()->whereNotNull('file_path')->pluck('file_path'),
                ...$user->posts()->whereNotNull('attachment_path')->pluck('attachment_path'),
            ]);
        });
    }

    /**
     * @return HasMany<Classroom, $this>
     */
    public function teachingClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    /**
     * @return BelongsToMany<Classroom, $this>
     */
    public function enrolledClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_user')->withTimestamps();
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(UserRole::Teacher->value);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(UserRole::Student->value);
    }

    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }
}
