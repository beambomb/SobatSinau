<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['teacher_id', 'title', 'subject', 'code'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * Characters used for join codes; visually ambiguous ones (0/o, 1/l/i) are excluded.
     */
    private const CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const CODE_LENGTH = 7;

    /**
     * Database cascades remove child rows without firing model events, so uploaded files are cleaned up here.
     */
    protected static function booted(): void
    {
        static::deleting(function (Classroom $classroom): void {
            $assignmentIds = $classroom->assignments()->select('id');

            Storage::disk('public')->delete([
                ...$classroom->posts()->whereNotNull('attachment_path')->pluck('attachment_path'),
                ...$classroom->assignments()->whereNotNull('attachment_path')->pluck('attachment_path'),
                ...Submission::whereIn('assignment_id', $assignmentIds)->whereNotNull('file_path')->pluck('file_path'),
            ]);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_user')->withTimestamps();
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Assignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function isTaughtBy(User $user): bool
    {
        return $this->teacher_id === $user->id;
    }

    public function hasStudent(User $user): bool
    {
        return $this->students()->whereKey($user->id)->exists();
    }

    public function isAccessibleBy(User $user): bool
    {
        return $user->isAdmin() || $this->isTaughtBy($user) || $this->hasStudent($user);
    }

    /**
     * Role of the given user inside this classroom, used by clients to tailor the UI.
     */
    public function viewerRoleFor(User $user): string
    {
        return match (true) {
            $this->isTaughtBy($user) => 'teacher',
            $user->isAdmin() => 'admin',
            default => 'student',
        };
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
