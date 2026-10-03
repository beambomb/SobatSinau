<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function view(User $user, Classroom $classroom): bool
    {
        return $classroom->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->isAdmin() || $classroom->isTaughtBy($user);
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $this->update($user, $classroom);
    }

    /**
     * Invite, remove students, create assignments, and post announcements/materials.
     */
    public function manage(User $user, Classroom $classroom): bool
    {
        return $this->update($user, $classroom);
    }

    public function join(User $user): bool
    {
        return $user->isStudent();
    }

    public function leave(User $user, Classroom $classroom): bool
    {
        return $classroom->hasStudent($user);
    }
}
