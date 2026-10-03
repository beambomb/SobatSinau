<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function view(User $user, Assignment $assignment): bool
    {
        return $assignment->classroom->isAccessibleBy($user);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isAdmin() || $assignment->classroom->isTaughtBy($user);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function viewSubmissions(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        return $user->isStudent() && $assignment->classroom->hasStudent($user);
    }
}
