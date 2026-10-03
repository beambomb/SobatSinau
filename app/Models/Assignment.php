<?php

namespace App\Models;

use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable(['classroom_id', 'teacher_id', 'title', 'instructions', 'attachment_path', 'attachment_name', 'due_date', 'max_points'])]
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'max_points' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Assignment $assignment): void {
            Storage::disk('public')->delete(array_filter([
                $assignment->attachment_path,
                ...$assignment->submissions()->whereNotNull('file_path')->pluck('file_path'),
            ]));
        });
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Unconstrained on purpose: callers scope it to the current student while eager loading.
     *
     * @return HasOne<Submission, $this>
     */
    public function mySubmission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }
}
